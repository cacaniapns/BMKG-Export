<?php
require 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['data']) && isset($_POST['time_type'])) {
    $timeType = $_POST['time_type'];
    
    // script ini hanya buat data jam-jaman
    if ($timeType !== 'hourly') {
        die('This script only handles hourly data exports.');
    }
    
    $data = unserialize(urldecode($_POST['data']));
    $variables = isset($_POST['variables']) ? explode(',', $_POST['variables']) : [];
    $variables = array_map('trim', array_filter($variables));
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : 'N/A';
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : 'N/A';
    $exportType = isset($_POST['export']) ? $_POST['export'] : 'word';
    
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
    
    // bikin dokumen baru
    $phpWord = new PhpWord();
    $phpWord->setDefaultFontName('Times New Roman');
    $phpWord->setDefaultFontSize(10);
    
    // tambah bagian dengan orientasi landscape
    $section = $phpWord->addSection([
        'orientation' => 'landscape',
        'marginTop' => 720,
        'marginBottom' => 720,
        'marginLeft' => 720,
        'marginRight' => 720,
    ]);
    
    $isFirstVariable = true;
    
    // iterate setiap variabel secara berurutan
    foreach ($variables as $variable) {
        // buat halaman baru sebelum variabel berikutnya (kecuali yang pertama)
        if (!$isFirstVariable) {
            $section->addPageBreak();
        }
        
        // KOP gambar hanya di halaman pertama
        if ($isFirstVariable && file_exists(__DIR__ . '/gambar/gambar/KOP.jpg')) {
            $section->addImage(
                __DIR__ . '/gambar/gambar/KOP.jpg',
                ['width' => 6000, 'alignment' => 'center']
            );
            $section->addParagraph('');
        }
        
        // judul untuk setiap variabel
        $titles = [
            'temperature' => 'LAPORAN DATA SUHU PER JAM',
            'humidity' => 'LAPORAN DATA KELEMBAPAN PER JAM',
            'wind' => 'LAPORAN DATA KECEPATAN ANGIN PER JAM'
        ];
        
        $section->addParagraph($titles[$variable], ['bold' => true, 'size' => 12, 'align' => 'center']);
        $section->addParagraph('Stasiun Meteorologi Sultan Syarif Kasim II Pekanbaru', ['align' => 'center', 'size' => 10]);
        $section->addParagraph('Periode: ' . $startDate . ' sampai ' . $endDate, ['align' => 'center', 'size' => 10]);
        $section->addParagraph('');
        
        // TABEL HORIZONTAL: kolom = jam (00-23), baris = tanggal
        // struktur: | Tanggal | 00 | 01 | 02 | ... | 23 |
        
        $table = $section->addTable([
            'borderSize' => 5,
            'cellMargin' => 15,
            'width' => 9900,
            'unit' => 'pct'
        ]);
        
        // ===== BARIS HEADER =====
        // kolom tanggal + 24 kolom jam
        $headerRow = $table->addRow();
        
        // header kolom tanggal
        $tanggalCell = $headerRow->addCell(500, [
            'bgColor' => '006B3F',
            'valign' => 'center',
            'vAlign' => 'center'
        ]);
        $tanggalCell->addParagraph('Tanggal', [
            'bold' => true,
            'color' => 'FFFFFF',
            'align' => 'center',
            'size' => 9
        ]);
        
        // header kolom jam (00 - 23)
        $hourCellWidth = 580; // lebar tiap kolom jam
        for ($hour = 0; $hour < 24; $hour++) {
            $hourCell = $headerRow->addCell($hourCellWidth, [
                'bgColor' => '006B3F',
                'valign' => 'center',
                'vAlign' => 'center'
            ]);
            $hourCell->addParagraph(sprintf('%02d', $hour), [
                'bold' => true,
                'color' => 'FFFFFF',
                'align' => 'center',
                'size' => 9
            ]);
        }
        
        // ===== BARIS DATA =====
        if (!empty($data)) {
            foreach ($data as $rowData) {
                $dataRow = $table->addRow();
                
                // kolom tanggal
                $dateCell = $dataRow->addCell(500, ['valign' => 'center', 'vAlign' => 'center']);
                $dateCell->addParagraph($rowData['tanggal'] ?? '-', [
                    'align' => 'center',
                    'size' => 9
                ]);
                
                // kolom nilai jam (00-23, satu per jam)
                for ($hour = 0; $hour < 24; $hour++) {
                    if ($variable === 'temperature') {
                        $key = 'temp_' . $hour;
                    } elseif ($variable === 'humidity') {
                        $key = 'humid_' . $hour;
                    } else { // wind
                        $key = 'wind_speed_' . $hour;
                    }
                    
                    // ambil nilai, kalo ga ada pake '-'
                    $value = '-';
                    if (isset($rowData[$key]) && $rowData[$key] !== null) {
                        $value = (string)$rowData[$key];
                    }
                    
                    $hourValueCell = $dataRow->addCell($hourCellWidth, ['valign' => 'center', 'vAlign' => 'center']);
                    $hourValueCell->addParagraph($value, [
                        'align' => 'center',
                        'size' => 9
                    ]);
                }
            }
        }
        
        $section->addParagraph('');
        $isFirstVariable = false;
    }
    
    // ====== halaman tanda tangan =====
    $section->addPageBreak();
    
    // spasi
    for ($i = 0; $i < 10; $i++) {
        $section->addParagraph('');
    }
    
    // lokasi dan tanggal (rata kanan)
    $section->addParagraph('Pekanbaru, ' . date('d F Y'), ['align' => 'right', 'size' => 10]);
    
    // judul (rata kanan)
    $section->addParagraph('Koordinator Bidang Prakiraan dan Informasi', ['align' => 'right', 'size' => 10, 'bold' => false]);
    
    // spasi buat tanda tangan
    $section->addParagraph('');
    $section->addParagraph('');
    $section->addParagraph('');
    
    // gambar tanda tangan (rata kanan)
    if (file_exists(__DIR__ . '/gambar/gambar/ttd.png')) {
        $section->addImage(
            __DIR__ . '/gambar/gambar/ttd.png',
            ['width' => 1000, 'alignment' => 'right']
        );
    }
    
    // Nama (rata kanan)
    $section->addParagraph('Bibin Sulianto, S.Si', ['align' => 'right', 'size' => 10]);
    
    // bikin nama file
    $variablesStr = implode('-', $variables);
    $filename = 'laporan-jam-' . $variablesStr . '-' . $startDate . '-' . $endDate;
    
    // setup folder sementara buat simpan file
    $tmpDir = __DIR__ . '/tmp_export/';
    if (!is_dir($tmpDir)) {
        @mkdir($tmpDir, 0777, true);
    }
    
    // ===== GENERATE HTML =====
    // baca KOP dan convert ke base64
    $kopBase64 = '';
    $kopPath = __DIR__ . '/gambar/gambar/KOP.jpg';
    if (file_exists($kopPath)) {
        $kopBase64 = base64_encode(file_get_contents($kopPath));
    }
    
    // baca image ttd terus convert ke base64
    $ttdBase64 = '';
    $ttdPath = __DIR__ . '/gambar/gambar/ttd.png';
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
            padding: 1cm;
            padding-top:1cm;
            box-sizing: border-box;
        }
        .page:last-child {
            page-break-after: avoid;
            margin: 0;
            padding: 1cm;
            box-sizing: border-box;
        }
        .page > * {
            margin: 0 !important;
        }
        .page > .table-label {
            margin-top: 1cm !important;
        }
        .page > table:first-of-type,
        .page > div:first-of-type {
            margin-top: 0 !important;
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
            margin: 0 ;
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
            margin-top: 1cm;
        }
        table {
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 1cm !important;
            font-size: 9pt;
            line-height: 1;
            border: 1px solid #000;
            width: 100%;
        }
        th {
            background-color: #398769;
            color: white;
            padding: 0px;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            border: 1px solid #000;
            height: 0.6cm;
            line-height: 0.6cm;
            vertical-align: middle;
        }
        td {
            border: 1px solid #000;
            padding: 0px;
            text-align: center;
            font-size: 9pt;
            height: 0.6cm;
            line-height: 0.6cm;
            vertical-align: middle;
        }
        .date-col {
            background-color: #E8E8E8;
            font-weight: bold;
            width: 2.2cm;
            color: black;
        }
        .hour-col {
            width: 1.05cm;
        }
        th.date-col {
            width: 2.2cm;
            color: black;
        }
        th.hour-col {
            font size: 8.5pt;
            width: 1.3cm;
        }
        .signature-section {
            margin-top: 5cm;
            padding-top: 5cm;
            page-break-inside: avoid;
            page-break-before: avoid;
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
    foreach ($variables as $variable) {
        if ($pageNum > 1) {
            $html .= '</div><div class="page">';
        } else {
            $html .= '<div class="page">';
        }
        
        // KOP hanya di halaman pertama
        // tempatkan gambar KOP dalam tabel 1 kolom supaya ukurannya stabil pas konversi LibreOffice
        if ($pageNum === 1 && !empty($kopBase64)) {
            $html .= '<table class="kop-table" style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
                        <tr>
                            <td style="border: none; padding: 0; height: 3.77cm; text-align: center; vertical-align: middle;">
                                <img src="data:image/jpeg;base64,' . $kopBase64 . '" alt="KOP" style="width: 100%; height: 3.77cm; display: block; max-width: 100%; object-fit: contain;">
                            </td>
                        </tr>
                      </table>';
            $html .= '<div style="margin-bottom: 3mm;"></div>';
        }
        
        // nama tampilan buat setiap variabel
        $variableNames = [
            'temperature' => 'Suhu',
            'humidity' => 'Kelembapan',
            'wind' => 'Angin'
        ];
        
        // cuma di variabel pertama aja yang tampilkan judul sama periode
        if ($pageNum === 1) {
            // bikin judul berdasarkan variabel yang dipilih
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
        
        // sub-label tabel - tampilkan nama variabel cuma kalo bukan angin
        if ($variable !== 'wind') {
            $html .= '<div class="table-label">' . $variableNames[$variable] . '</div>';
        }
        
        // kalo wind, tampilkan 2 tabel (arah dulu, terus kecepatan)
        $windTypes = ($variable === 'wind') ? ['direction', 'speed'] : [null];
        
        $isFirstTableInPage = true;
        foreach ($windTypes as $windType) {
            // kalo wind, tambah sub-label buat arah dan kecepatan dengan spasi
            if ($variable === 'wind') {
                if ($windType === 'direction') {
                    $html .= '<div class="table-label" style="margin-top: 0; margin-bottom: 0;">Arah Angin</div>';
                } else {
                    $html .= '<div class="table-label" style="margin-top: 1cm; margin-bottom: 0;">Kecepatan Angin</div>';
                }
            } else {
                // buat non-wind, label udah ditampilkan di atas
                $html .= '';
            }
        
        // header tabel
        // tambah margin-top di tabel pertama di halaman yang bukan pertama
        $tableStyle = ($pageNum > 1 && $isFirstTableInPage) ? ' style="margin-top: 1cm;"' : '';
        $html .= '<table' . $tableStyle . '><thead><tr>';
        $html .= '<th class="date-col">Tanggal</th>';
        for ($hour = 0; $hour < 24; $hour++) {
            $html .= '<th class="hour-col">' . sprintf('%02d:00', $hour) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        
        // baris data
        if (!empty($data)) {
            foreach ($data as $rowData) {
                $html .= '<tr>';
                
                // kolom tanggal
                $html .= '<td class="date-col">' . ($rowData['tanggal'] ?? '-') . '</td>';
                
                // kolom nilai jam
                for ($hour = 0; $hour < 24; $hour++) {
                    if ($variable === 'temperature') {
                        $key = 'temp_' . $hour;
                    } elseif ($variable === 'humidity') {
                        $key = 'humid_' . $hour;
                    } elseif ($variable === 'wind') {
                        // buat wind, gunakan speed atau direction sesuai windType
                        $key = ($windType === 'direction') ? 'wind_dir_' . $hour : 'wind_speed_' . $hour;
                    }
                    
                    $value = isset($rowData[$key]) && $rowData[$key] !== null ? $rowData[$key] : '-';
                    $html .= '<td class="hour-col">' . $value . '</td>';
                }
                
                $html .= '</tr>';
            }
        }
        
        $html .= '</tbody></table>';
        
        // tandai bahwa tabel pertama udah ditampilkan
        $isFirstTableInPage = false;
        
        } // end foreach windTypes
        
        // jangan tutup page div dulu kalo masih ada variabel lain
        if ($pageNum < count($variables)) {
            $html .= '</div>';
        }
        $pageNum++;
    }
    
    // fungsi buat format tanggal dalam bahasa Indonesia
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
    
    // bagian tanda tangan di halaman terakhir, dalam .page yang sama dengan tabel terakhir
    $html .= '
    <div class="signature-section">
        <div class="signature-text">
            <p style="margin-bottom: 2px;">Pekanbaru, ' . $tanggal_sekarang . '</p>
            <p style="margin-bottom: 20px; margin-top: 2px;">Koordinator Bidang Prakiraan dan Informasi</p>';
    
    if (!empty($ttdBase64)) {
        $html .= '<div class="signature-image">
                    <img src="data:image/png;base64,' . $ttdBase64 . '" alt="ttd" style="width: 1.72cm; height: 2cm;">
                  </div>';
    }
    
    $html .= '        <p class="signature-name" style="margin-top: 5px;"><u>Bibin Sulianto, S.Si</u></p>
        </div>
    </div>
    </div>
</body>
</html>';
    
    // simpan HTML ke file sementara
    $htmlPath = $tmpDir . $filename . '.html';
    file_put_contents($htmlPath, $html);
    
    // ===== KONVERSI HTML KE PDF PAKE DOMPDF =====
    if ($exportType === 'pdf') {
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
            
            // set ukuran kertas dan orientasi
            $dompdf->setPaper('A4', 'landscape');
            
            // render PDF
            $dompdf->render();
            
            // output
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
            header('Cache-Control: max-age=0');
            header('Pragma: no-cache');
            
            echo $dompdf->output();
            
            // bersih-bersih
            @unlink($htmlPath);
            exit;
            
        } catch (Exception $e) {
            // kalo error, output pesan error
            http_response_code(500);
            die('Error generating PDF: ' . htmlspecialchars($e->getMessage()));
        }
    } else {
        // buat Word, output HTML
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline;filename="' . $filename . '.html"');
        readfile($htmlPath);
        exit;
    }
    
} else {
    die('Data atau format export ga ditemukan.');
}
?>