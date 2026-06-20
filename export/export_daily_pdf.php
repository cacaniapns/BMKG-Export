<?php
ini_set('memory_limit', '1024M');
date_default_timezone_set('Asia/Jakarta');

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['data']) && isset($_POST['time_type'])) {
    $timeType = $_POST['time_type'];
    
    if ($timeType !== 'daily') {
        die('Script ini cuma buat export data harian.');
    }
    
    $data = unserialize(urldecode($_POST['data']));
    $variables = isset($_POST['variables']) ? explode(',', $_POST['variables']) : [];
    $variables = array_map('trim', array_filter($variables));
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : 'N/A';
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : 'N/A';
    
    $validVariables = ['temperature', 'humidity', 'wind', 'rainfall'];
    $variables = array_intersect($variables, $validVariables);
    
    if (empty($variables)) {
        die('Tidak ada variabel yang dipilih untuk daily export.');
    }
    
    // baca KOP terus convert ke base64
    $kopBase64 = '';
    $kopPath = __DIR__ . '/../gambar/gambar/KOP.jpg';
    if (file_exists($kopPath)) {
        $kopBase64 = base64_encode(file_get_contents($kopPath));
    }
    
    // baca image ttd terus convert ke base64
    $ttdBase64 = '';
    $ttdPath = __DIR__ . '/../gambar/gambar/ttd.png';
    if (file_exists($ttdPath)) {
        $ttdBase64 = base64_encode(file_get_contents($ttdPath));
    }
    
    // fungsi buat ubah derajat jadi arah mata angin
    function degToCompass($deg) {
        if ($deg === null || $deg === '') return '-';
        $d = floatval($deg);
        if ($d > 337.5 || $d <= 22.5) return 'N';
        if ($d <= 67.5) return 'NE';
        if ($d <= 112.5) return 'E';
        if ($d <= 157.5) return 'SE';
        if ($d <= 202.5) return 'S';
        if ($d <= 247.5) return 'SW';
        if ($d <= 292.5) return 'W';
        return 'NW';
    }
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm;
            margin-top: 2.5cm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body, html {
            margin: 0;
            padding: 0 10mm;
        }
        
        body {
            font-family: "Times New Roman", "Arial", sans-serif;
            font-size: 10pt;
            line-height: 1.2;
            padding-top: 10mm;
            padding-bottom: 10mm;
        }

        thead {
            display: table-header-group;
        }
        
        .page-wrapper {
            display: block;
            margin: 0;
            padding: 0;
        }
        
        .page-wrapper:last-child {
            page-break-after: avoid;
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
            margin-top: 1mm;
            line-height: 1.1;
        }
        
        .subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin: 0;
            margin-top: 0.5mm;
            line-height: 1.1;
        }
        
        .period {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin: 0;
            margin-top: 0.5mm;
            margin-bottom: 2mm;
            line-height: 1.1;
        }
        
        .table-label {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin: 0;
            margin-top: 0;
        }
        
        table {
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9pt;
            line-height: 1;
            border: 1px solid #000;
            width: 100%;
        }
        
        th {
            background-color: #398769;
            color: white;
            padding: 3px 2px;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            border: 1px solid #000;
            height: auto;
            line-height: 1.2;
            vertical-align: middle;
        }
        
        td {
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            font-size: 9pt;
            height: auto;
            line-height: 1.2;
            vertical-align: middle;
        }
        
        .date-col {
            background-color: #E8E8E8;
            font-weight: bold;
            color: black;
            text-align: center;
        }
        
        .signature-section {
            margin-top: 0.5cm;
            padding-top: 0.3cm;
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
    
    // format tanggal buat ditampilin
    $startDateObj = new DateTime($startDate);
    $endDateObj = new DateTime($endDate);
    $periodText = $startDateObj->format('d F Y') . ' - ' . $endDateObj->format('d F Y');
    
    $months = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];
    
    foreach ($months as $en => $id) {
        $periodText = str_replace($en, $id, $periodText);
    }
    
    // KOP gambar
    if (!empty($kopBase64)) {
        $html .= '<table class="kop-table" style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
                    <tr>
                        <td style="border: none; padding: 0; height: 3.77cm; text-align: center; vertical-align: middle;">
                            <img src="data:image/jpeg;base64,' . $kopBase64 . '" alt="KOP" style="width: 100%; height: 3.77cm; display: block; max-width: 100%; object-fit: contain;">
                        </td>
                    </tr>
                  </table>';
        $html .= '<div style="margin-bottom: 3mm;"></div>';
    }
    
    // judul dengan grammar yang bener
    $variableNames = [
        'temperature' => 'Suhu',
        'humidity' => 'Kelembapan',
        'wind' => 'Angin',
        'rainfall' => 'Curah Hujan'
    ];
    
    $variableLabels = [];
    foreach ($variables as $var) {
        if (isset($variableNames[$var])) {
            $variableLabels[] = $variableNames[$var];
        }
    }
    
    $title = 'Data Harian ';
    if (count($variableLabels) === 1) {
        $title .= $variableLabels[0];
    } elseif (count($variableLabels) === 2) {
        $title .= implode(' dan ', $variableLabels);
    } else {
        $title .= implode(', ', array_slice($variableLabels, 0, -1)) . ', dan ' . end($variableLabels);
    }
    
    $html .= '<p class="title">' . htmlspecialchars($title) . '</p>';
    $html .= '<p class="subtitle">Kota Pekanbaru, Provinsi Riau</p>';
    $html .= '<p class="period">Periode ' . htmlspecialchars($periodText) . '</p>';
    
    // hitung jumlah kolom berdasarkan variabel yang dipilih
    $columnCount = 1; // Kolom tanggal
    $dataColumnCount = 0; // Kolom data (tidak termasuk tanggal)
    if (in_array('temperature', $variables)) {
        $columnCount += 3; // Suhu Max, Min, Rata-Rata
        $dataColumnCount += 3;
    }
    if (in_array('humidity', $variables)) {
        $columnCount += 3; // Kelembapan Max, Min, Rata-Rata
        $dataColumnCount += 3;
    }
    if (in_array('wind', $variables)) {
        $columnCount += 4; // Kec. Angin Max, Arah, Rata-Rata Kec, Rata-Rata Arah
        $dataColumnCount += 4;
    }
    if (in_array('rainfall', $variables)) {
        $columnCount += 1; // Curah Hujan
        $dataColumnCount += 1;
    }
    
    // tentukan aturan page berdasarkan jumlah kolom data (tidak termasuk tanggal)
    if ($dataColumnCount <= 7) {
        // Jika kolom data <= 7: maksimal 45 baris per halaman (middle page), 34 baris untuk last page
        $maxRowsFirstPage = 31;
        $maxRowsMiddlePages = 45;
        $maxRowsLastPage = 34;
    } else {
        // Jika kolom data > 7: 19 baris first page, 27 baris middle page, 20 baris last page dengan signature
        $maxRowsFirstPage = 19;
        $maxRowsMiddlePages = 27;
        $maxRowsLastPage = 20;
    }
    
    $html .= '<div class="page-wrapper">';
    // bikin tabel
    $html .= '<table>';
    $html .= '<thead><tr>';
    
    $html .= '<th class="date-col">Tanggal</th>';
    
    if (in_array('temperature', $variables)) {
        $html .= '<th>Suhu Max<br>(°C)</th>';
        $html .= '<th>Suhu Min<br>(°C)</th>';
        $html .= '<th>Rata-Rata Suhu<br>(°C)</th>';
    }
    
    if (in_array('humidity', $variables)) {
        $html .= '<th>Kelembapan Max<br>(%)</th>';
        $html .= '<th>Kelembapan Min<br>(%)</th>';
        $html .= '<th>Rata-Rata Kelembapan<br>(%)</th>';
    }
    
    if (in_array('wind', $variables)) {
        $html .= '<th>Kec. Angin Max<br>(m/s)</th>';
        $html .= '<th>Arah Angin</th>';
        $html .= '<th>Rata-Rata Kec. Angin<br>(m/s)</th>';
        $html .= '<th>Rata-Rata Arah Angin</th>';
    }
    
    if (in_array('rainfall', $variables)) {
        $html .= '<th>Curah Hujan<br>(mm)</th>';
    }
    
    $html .= '</tr></thead>';
    $html .= '<tbody>';
    
    if (is_array($data) && !empty($data)) {
        $rowCount = 0;
        $rowsInCurrentPage = 0;
        $isFirstPage = true;
        $totalDataRows = count($data);
        $currentDataIndex = 0;
        
        foreach ($data as $row) {
            // hitung sisa data buat tentukan apakah halaman terakhir
            $remainingRows = $totalDataRows - $currentDataIndex;
            
            // tentukan max rows berdasarkan halaman
            if ($isFirstPage) {
                $maxRowsThisPage = $maxRowsFirstPage;
            } else {
                // Untuk halaman bukan pertama, cek apakah akan menjadi halaman terakhir
                $maxRowsThisPage = ($remainingRows <= $maxRowsLastPage) ? $maxRowsLastPage : $maxRowsMiddlePages;
            }
            
            // kalo udah mencapai max rows di halaman saat ini, bikin halaman baru
            if ($rowsInCurrentPage >= $maxRowsThisPage) {
                $html .= '</tbody></table>';
                $html .= '</div>';  // Close page-wrapper
                $html .= '<div class="page-wrapper">';
                
                // buka tabel baru di halaman berikutnya
                $html .= '<table style="page-break-before: always;">';
                $html .= '<thead><tr>';
                $html .= '<th class="date-col">Tanggal</th>';
                
                if (in_array('temperature', $variables)) {
                    $html .= '<th>Suhu Max<br>(°C)</th>';
                    $html .= '<th>Suhu Min<br>(°C)</th>';
                    $html .= '<th>Rata-Rata Suhu<br>(°C)</th>';
                }
                
                if (in_array('humidity', $variables)) {
                    $html .= '<th>Kelembapan Max<br>(%)</th>';
                    $html .= '<th>Kelembapan Min<br>(%)</th>';
                    $html .= '<th>Rata-Rata Kelembapan<br>(%)</th>';
                }
                
                if (in_array('wind', $variables)) {
                    $html .= '<th>Kec. Angin Max<br>(m/s)</th>';
                    $html .= '<th>Arah Angin</th>';
                    $html .= '<th>Rata-Rata Kec. Angin<br>(m/s)</th>';
                    $html .= '<th>Rata-Rata Arah Angin</th>';
                }
                
                if (in_array('rainfall', $variables)) {
                    $html .= '<th>Curah Hujan<br>(mm)</th>';
                }
                
                $html .= '</tr></thead>';
                $html .= '<tbody>';
                
                // setelah page break pertama, ubah max rows dan reset counter
                $isFirstPage = false;
                $rowsInCurrentPage = 0;
            }
            
            $html .= '<tr>';
            
            $date = isset($row['tanggal']) ? $row['tanggal'] : '-';
            $html .= '<td class="date-col">' . htmlspecialchars($date) . '</td>';
            
            if (in_array('temperature', $variables)) {
                $maxTemp = isset($row['temp_max']) && $row['temp_max'] !== null ? number_format((float)$row['temp_max'], 1) : '-';
                $minTemp = isset($row['temp_min']) && $row['temp_min'] !== null ? number_format((float)$row['temp_min'], 1) : '-';
                $avgTemp = isset($row['temp_avg']) && $row['temp_avg'] !== null ? number_format((float)$row['temp_avg'], 1) : '-';
                
                $html .= '<td>' . htmlspecialchars($maxTemp) . '</td>';
                $html .= '<td>' . htmlspecialchars($minTemp) . '</td>';
                $html .= '<td>' . htmlspecialchars($avgTemp) . '</td>';
            }
            
            if (in_array('humidity', $variables)) {
                $maxHum = isset($row['humid_max']) && $row['humid_max'] !== null ? number_format((float)$row['humid_max'], 1) : '-';
                $minHum = isset($row['humid_min']) && $row['humid_min'] !== null ? number_format((float)$row['humid_min'], 1) : '-';
                $avgHum = isset($row['humid_avg']) && $row['humid_avg'] !== null ? number_format((float)$row['humid_avg'], 1) : '-';
                
                $html .= '<td>' . htmlspecialchars($maxHum) . '</td>';
                $html .= '<td>' . htmlspecialchars($minHum) . '</td>';
                $html .= '<td>' . htmlspecialchars($avgHum) . '</td>';
            }
            
            if (in_array('wind', $variables)) {
                $windSpeedMax = isset($row['wind_speed_max']) && $row['wind_speed_max'] !== null ? number_format((float)$row['wind_speed_max'], 1) : '-';
                $windDirMax = isset($row['wind_dir_max']) ? degToCompass($row['wind_dir_max']) : '-';
                $windSpeedAvg = isset($row['wind_speed_avg']) && $row['wind_speed_avg'] !== null ? number_format((float)$row['wind_speed_avg'], 1) : '-';
                $windDirAvg = isset($row['wind_dir_avg']) ? $row['wind_dir_avg'] : '-';
                
                $html .= '<td>' . htmlspecialchars($windSpeedMax) . '</td>';
                $html .= '<td>' . htmlspecialchars($windDirMax) . '</td>';
                $html .= '<td>' . htmlspecialchars($windSpeedAvg) . '</td>';
                $html .= '<td>' . htmlspecialchars($windDirAvg) . '</td>';
            }
            
            if (in_array('rainfall', $variables)) {
                $rainfall = isset($row['rainfall']) && $row['rainfall'] !== null ? number_format((float)$row['rainfall'], 1) : '-';
                $html .= '<td>' . htmlspecialchars($rainfall) . '</td>';
            }
            
            $html .= '</tr>';
            $rowCount++;
            $rowsInCurrentPage++;
            $currentDataIndex++;
        }
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    // tanda tangan di halaman yang sama dengan tabel (dalam page-wrapper)
    $tanggal_sekarang = date('d F Y');
    foreach ($months as $en => $id) {
        $tanggal_sekarang = str_replace($en, $id, $tanggal_sekarang);
    }
    
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
    
    $html .= '</div>';  // Close page-wrapper
    $html .= '
</body>
</html>';
    
    // bikin nama file
    $variablesStr = implode('-', $variables);
    $filename = 'laporan-hari-' . $variablesStr . '-' . $startDate . '-' . $endDate;
    
    // ===== KONVERSI HTML KE PDF PAKE DOMPDF =====
    try {
        // setup opsi Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('isFontSubsettingEnabled', true);
        
        // bikin instance Dompdf
        $dompdf = new Dompdf($options);
        
        // load HTML
        $dompdf->loadHtml($html);
        
        // set ukuran kertas, orientasi, sama margin
        $dompdf->setPaper('A4', 'portrait');
        
        // render PDF
        $dompdf->render();
        
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
