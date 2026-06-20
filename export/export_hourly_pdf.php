<?php
ini_set('memory_limit', '2048M');
date_default_timezone_set('Asia/Jakarta');

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['data']) && isset($_POST['time_type'])) {
    $timeType = $_POST['time_type'];
    
    if ($timeType !== 'hourly') {
        die('Script ini cuma buat export data jam-jaman.');
    }
    
    $data = unserialize(urldecode($_POST['data']));
    $variables = isset($_POST['variables']) ? explode(',', $_POST['variables']) : [];
    $variables = array_map('trim', array_filter($variables));
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : 'N/A';
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : 'N/A';
    
    $validVariables = ['temperature', 'humidity', 'wind'];
    $variables = array_intersect($variables, $validVariables);
    
    // supaya urutan tabel selalu sama: suhu, kelembapan, angin
    $orderedVariables = [];
    if (in_array('temperature', $variables)) $orderedVariables[] = 'temperature';
    if (in_array('humidity', $variables)) $orderedVariables[] = 'humidity';
    if (in_array('wind', $variables)) $orderedVariables[] = 'wind';
    $variables = $orderedVariables;
    
    if (empty($variables)) {
        die('Tidak ada variabel yang dipilih untuk hourly export.');
    }
    
    // ===== VALIDASI RANGE TANGGAL BUAT EXPORT JAM-JAMAN =====
    // Untuk export jam-jaman, batasi maksimal 90 hari untuk menghindari memory exhaustion
    try {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        $daysDifference = $interval->days + 1; // +1 karena include start dan end date
        
        if ($daysDifference > 90) {
            http_response_code(400);
            die('<html><head><meta charset="UTF-8"><title>Error Export</title><style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5}h1{color:#d32f2f}p{color:#333;line-height:1.6}.error-box{background:#fff;border-left:4px solid #d32f2f;padding:20px;margin:20px 0}.suggestion{background:#e8f5e9;border-left:4px solid #4caf50;padding:20px;margin:20px 0}.suggestion a{color:#4caf50;text-decoration:none;font-weight:bold}</style></head><body><h1>❌ Rentang Tanggal Terlalu Panjang</h1><div class="error-box"><p><strong>Error:</strong> Export data jam-jaman tidak bisa lebih dari 90 hari karena keterbatasan memori server.</p><p>Anda mencoba mengekspor <strong>' . $daysDifference . ' hari</strong> data (dari ' . $startDate . ' sampai ' . $endDate . ').</p></div><div class="suggestion"><p><strong>💡 Solusi:</strong></p><ul><li><strong>Pilih range tanggal lebih kecil</strong> (maksimal 90 hari)</li><li><strong>Gunakan Excel Export</strong> untuk data dalam jumlah besar (tekan tombol "Export Excel" di Data.php) - Excel lebih efisien untuk data besar</li><li><strong>Gunakan Monthly atau Daily view</strong> jika hanya butuh summary data</li></ul></div></body></html>');
        }
    } catch (Exception $e) {
        http_response_code(400);
        die('Error: Tanggal tidak valid. ' . htmlspecialchars($e->getMessage()));
    }
    
    // ===== FUNGSI BUAT HITUNG DISTRIBUSI BARIS =====
    function calculateRowDistribution($totalRows, $maxFirstPage, $maxMiddlePages, $maxLastPage) {
        $pages = [];
        $remaining = $totalRows;
        $currentPageNum = 1;
        
        while ($remaining > 0) {
            if ($currentPageNum === 1) {
                $maxThisPage = $maxFirstPage;
            } elseif ($remaining <= $maxLastPage) {
                // kalo sisa <= maxLastPage, gunakan semua buat halaman terakhir
                $maxThisPage = $remaining;
            } else {
                $maxThisPage = $maxMiddlePages;
            }
            
            $rowsThisPage = min($maxThisPage, $remaining);
            $pages[] = ['page' => $currentPageNum, 'rows' => $rowsThisPage];
            $remaining -= $rowsThisPage;
            $currentPageNum++;
        }
        
        return $pages;
    }
    
    // baca KOP dan convert ke base64
    $kopBase64 = '';
    $kopPath = __DIR__ . '/../gambar/gambar/KOP.jpg';
    if (file_exists($kopPath)) {
        $kopBase64 = base64_encode(file_get_contents($kopPath));
    }
    
    // baca image ttd lalu convert ke base64
    $ttdBase64 = '';
    $ttdPath = __DIR__ . '/../gambar/gambar/ttd.png';
    if (file_exists($ttdPath)) {
        $ttdBase64 = base64_encode(file_get_contents($ttdPath));
    }
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm;
        }
        
        body, html {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: "Times New Roman", "Arial", sans-serif;
            font-size: 10pt;
            line-height: 1.2;
        }

        thead {
            display: table-header-group;
        }

        * {
            margin: 0;
            padding: 0;
        }
        
        .page {
            page-break-after: always;
            margin: 0;
            padding: 0.6cm;
            box-sizing: border-box;
        }
        
        .page:last-child {
            page-break-after: avoid;
            margin: 0;
            padding: 0.6cm;
            box-sizing: border-box;
        }
        
        .page.new-variable {
            padding-top: 1.2cm;
        }
        
        .page > table {
            margin-top: 0;
        }
        
        .page:first-child > table {
            page-break-before: auto;
        }
        
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 !important;
            padding: 0;
        }
        
        .kop-table td {
            border: none;
            padding: 0;
            height: 3.77cm;
            text-align: center;
            vertical-align: middle;
        }
        
        .kop-table img {
            width: 100%;
            height: 3.77cm !important;
            display: block;
            max-width: 100%;
            object-fit: contain;
        }
        
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin: 0;
        }
        
        .subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin: 0;
        }
        
        .period {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin: 0;
        }
        
        .table-label {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin: 0;
            margin-top: 0.15cm;
            margin-bottom: 0.05cm;
        }
        
        table {
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8pt;
            line-height: 1;
            border: 1px solid #000;
            width: 100%;
            margin-top: 0 !important;
        }
        
        th {
            background-color: #398769;
            color: white;
            padding: 0px;
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
            border: 1px solid #000;
            height: 0.5cm;
            line-height: 0.5cm;
            vertical-align: middle;
        }
        
        td {
            border: 1px solid #000;
            padding: 0px;
            text-align: center;
            font-size: 8pt;
            height: 0.5cm;
            line-height: 0.5cm;
            vertical-align: middle;
        }
        
        .date-col {
            background-color: #E8E8E8;
            font-weight: bold;
            width: 2cm;
            color: black;
        }
        
        .hour-col {
            width: 1cm;
        }
        
        th.date-col {
            width: 2cm;
            color: black;
        }
        
        th.hour-col {
            font-size: 7.5pt;
            width: 1cm;
        }
        
        .signature-section {
            margin-top: 3cm;
            padding-top: 0.15cm;
            page-break-inside: avoid;
        }
        
        .signature-text {
            text-align: right;
            margin-right: 0mm;
            margin-left: 0mm;
            font-size: 11pt;
        }
        
        .signature-image {
            text-align: right;
            margin-right: 10mm;
            margin-left: 0mm;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        .signature-image img {
            max-height: 60px;
        }
        
        .signature-name {
            text-align: right;
            margin-right: 0mm;
            margin-left: 0mm;
            font-size: 11pt;
            margin-top: 10px;
        }
    </style>
</head>
<body>';
    
    $pageNum = 1;
    $isFirstPageOfDocument = true;  // track halaman pertama dokumen keseluruhan
    foreach ($variables as $variable) {
        if ($pageNum > 1) {
            $html .= '</div><div class="page new-variable">';
        } else {
            $html .= '<div class="page">';
        }
        
        // KOP gambar cuma di halaman pertama
        if ($pageNum === 1 && !empty($kopBase64)) {
            $html .= '<table class="kop-table" style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
                        <tr>
                            <td style="border: none; padding: 0; height: 3.77cm; text-align: center; vertical-align: middle;">
                                <img src="data:image/jpeg;base64,' . $kopBase64 . '" alt="KOP" style="width: 100%; height: 3.77cm; display: block; max-width: 100%; object-fit: contain;">
                            </td>
                        </tr>
                      </table>';
            $html .= '<div style="margin-bottom: 1.5mm;"></div>';
        }
        
        // nama tampilan buat setiap variabel
        $variableNames = [
            'temperature' => 'Suhu',
            'humidity' => 'Kelembapan',
            'wind' => 'Angin'
        ];
        
        // cuma di variabel pertama aja yang tampilkan judul sama periode
        if ($pageNum === 1) {
            $variableLabels = array_map(function($v) use ($variableNames) {
                return $variableNames[$v];
            }, $variables);
            
            if (count($variableLabels) === 1) {
                $titleText = 'Data ' . $variableLabels[0] . ' Per-Jam';
            } elseif (count($variableLabels) === 2) {
                $titleText = 'Data ' . implode(' dan ', $variableLabels) . ' Per-Jam';
            } else {
                $titleText = 'Data ' . implode(', ', array_slice($variableLabels, 0, -1)) . ', dan ' . end($variableLabels) . ' Per-Jam';
            }
            
            $html .= '<div class="title">' . $titleText . '</div>';
            $html .= '<div class="subtitle">Kota Pekanbaru, Provinsi Riau</div>';
            $html .= '<div class="period">Periode ' . $startDate . ' sampai ' . $endDate . '</div>';
        }
        
        // sub-label tabel
        if ($variable !== 'wind') {
            $html .= '<div class="table-label">' . $variableNames[$variable] . '</div>';
        }
        
        // kalo wind, tampilkan 2 tabel
        $windTypes = ($variable === 'wind') ? ['direction', 'speed'] : [null];
        
        $totalDataRows = count($data);
        // 10 baris cuma buat halaman pertama dokumen, variabel baru (non-first page) 15 baris
        $maxRowsFirstPage = $isFirstPageOfDocument ? 10 : 15;
        $maxRowsMiddlePages = 16;
        $maxRowsLastPage = 12;
        
        // hitung distribusi baris buat setiap halaman
        $pageDistribution = calculateRowDistribution($totalDataRows, $maxRowsFirstPage, $maxRowsMiddlePages, $maxRowsLastPage);
        
        // tracking buat page breaks
        $pageIndex = 0;
        $rowsRendered = 0;
        $currentPageRowCount = 0;
        
        foreach ($windTypes as $windType) {
            if ($variable === 'wind') {
                if ($windType === 'direction') {
                    $html .= '<div class="table-label" style="margin-top: 0; margin-bottom: 0;">Arah Angin</div>';
                } else {
                    $html .= '<div class="table-label" style="margin-top: 1cm; margin-bottom: 0;">Kecepatan Angin</div>';
                }
            }
        
            $html .= '<table>
            <thead>
            <tr>';
            $html .= '<th class="date-col">Tanggal</th>';
            for ($hour = 0; $hour < 24; $hour++) {
                $html .= '<th class="hour-col">' . sprintf('%02d:00', $hour) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            
            // baris data
            if (!empty($data)) {
                foreach ($data as $rowIndex => $rowData) {
                    // ambil jumlah baris yang seharusnya di halaman saat ini
                    if ($pageIndex < count($pageDistribution)) {
                        $targetRowsThisPage = $pageDistribution[$pageIndex]['rows'];
                    } else {
                        $targetRowsThisPage = $maxRowsMiddlePages;
                    }
                    
                    // kalo udah mencapai target baris buat halaman ini, bikin page break
                    if ($currentPageRowCount >= $targetRowsThisPage) {
                        $html .= '</tbody></table>';
                        $html .= '</div>';  // Close current page
                        $html .= '<div class="page new-variable">';  // Open new page
                        
                        // buka tabel baru di halaman berikutnya TANPA label (label cuma di awal)
                        $html .= '<table>
                        <thead>
                        <tr>';
                        $html .= '<th class="date-col">Tanggal</th>';
                        for ($hour = 0; $hour < 24; $hour++) {
                            $html .= '<th class="hour-col">' . sprintf('%02d:00', $hour) . '</th>';
                        }
                        $html .= '</tr></thead><tbody>';
                        
                        $pageIndex++;
                        $currentPageRowCount = 0;
                    }
                    
                    $html .= '<tr>';
                    $html .= '<td class="date-col">' . ($rowData['tanggal'] ?? '-') . '</td>';
                    
                    for ($hour = 0; $hour < 24; $hour++) {
                        if ($variable === 'temperature') {
                            $key = 'temp_' . $hour;
                        } elseif ($variable === 'humidity') {
                            $key = 'humid_' . $hour;
                        } elseif ($variable === 'wind') {
                            $key = ($windType === 'direction') ? 'wind_dir_' . $hour : 'wind_speed_' . $hour;
                        }
                        
                        $value = isset($rowData[$key]) && $rowData[$key] !== null ? $rowData[$key] : '-';
                        $html .= '<td class="hour-col">' . $value . '</td>';
                    }
                    
                    $html .= '</tr>';
                    $rowsRendered++;
                    $currentPageRowCount++;
                }
            }
            
            $html .= '</tbody></table>';
            
            // buat wind, pisahkan 2 tabel ke halaman berbeda
            if ($variable === 'wind' && $windType === 'direction') {
                $html .= '</div>';  // Tutup halaman saat ini setelah tabel Arah Angin
                $html .= '<div class="page new-variable">';  // Buka halaman baru buat tabel Kecepatan Angin
                // reset counter buat tabel kecepatan angin
                $pageIndex = 0;
                $currentPageRowCount = 0;
            }
        }
        
        if ($pageNum < count($variables)) {
            $html .= '</div>';
        }
        $pageNum++;
        $isFirstPageOfDocument = false;  // setelah variabel pertama, update flag
    }
    
    // tanda tangan di halaman baru (terpisah dari tabel)
    $html .= '<div class="page">';
    $bulan_indonesia = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    $tanggal_sekarang = date('d F Y');
    foreach ($bulan_indonesia as $en => $id) {
        $tanggal_sekarang = str_replace($en, $id, $tanggal_sekarang);
    }
    
    // bagian tanda tangan
    $html .= '
    <div class="signature-section">
        <div class="signature-text">
            <p style="margin-bottom: 2px;">Pekanbaru, ' . $tanggal_sekarang . '</p>
            <p style="margin-bottom: 20px; margin-top: 2px;">Koordinator Bidang Prakiraan dan Informasi</p>';
    
    if (!empty($ttdBase64)) {
        $html .= '<div class="signature-image">
                    <div style="height: 2cm;"></div>
                  </div>';
    }
    
    $html .= '        <p class="signature-name" style="margin-top: 5px;"><u>Bibin Sulianto, S.Si</u></p>
        </div>
    </div>';
    
    $html .= '</div>';  // Close last page div
    
    // bikin nama file
    $variablesStr = implode('-', $variables);
    $filename = 'laporan-jam-' . $variablesStr . '-' . $startDate . '-' . $endDate;
    
    // ===== KONVERSI HTML KE PDF PAKE DOMPDF =====
    try {
        // setup opsi Dompdf dengan optimasi memory
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('enableJavaScript', false);
        $options->set('debugPng', false);
        $options->set('debugKeepTemp', false);
        $options->set('logOutputFile', null);
        
        // bikin instance Dompdf
        $dompdf = new Dompdf($options);
        
        // load HTML dengan UTF-8 encoding
        $dompdf->loadHtml($html, 'UTF-8');
        
        // set ukuran kertas sama orientasi
        $dompdf->setPaper('A4', 'landscape');
        
        // render PDF dengan memory boost
        $originalLimit = ini_get('memory_limit');
        ini_set('memory_limit', '3072M');
        $dompdf->render();
        ini_set('memory_limit', $originalLimit);
        
        // output
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');
        
        echo $dompdf->output();
        exit;
        
    } catch (Exception $e) {
        // kalo error, output pesan error
        http_response_code(500);
        die('Error generating PDF: ' . htmlspecialchars($e->getMessage()));
    }
    
} else {
    die('Data atau format export ga ditemukan.');
}
?>
