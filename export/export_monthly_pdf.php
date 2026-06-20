<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['data']) && isset($_POST['time_type'])) {
    $timeType = $_POST['time_type'];
    
    if ($timeType !== 'monthly') {
        die('Script ini cuma buat export data bulanan.');
    }
    
    $data = unserialize(urldecode($_POST['data']));
    $variables = isset($_POST['variables']) ? explode(',', $_POST['variables']) : [];
    $variables = array_map('trim', array_filter($variables));
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : 'N/A';
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : 'N/A';
    
    $validVariables = ['temperature', 'humidity', 'wind', 'rainfall'];
    $variables = array_intersect($variables, $validVariables);
    
    if (empty($variables)) {
        die('Tidak ada variabel yang dipilih untuk monthly export.');
    }
    
    // fungsi buat ubah derajat jadi arah mata angin (English)
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
    
    $title = 'Data Bulanan ';
    if (count($variableLabels) === 1) {
        $title .= $variableLabels[0];
    } elseif (count($variableLabels) === 2) {
        $title .= implode(' dan ', $variableLabels);
    } else {
        $title .= implode(', ', array_slice($variableLabels, 0, -1)) . ', dan ' . end($variableLabels);
    }
    
    // format periode
    $startDateObj = new DateTime($startDate);
    $endDateObj = new DateTime($endDate);
    
    $startFormatted = $startDateObj->format('d F Y');
    $endFormatted = $endDateObj->format('d F Y');
    
    foreach ($months as $en => $id) {
        $startFormatted = str_replace($en, $id, $startFormatted);
        $endFormatted = str_replace($en, $id, $endFormatted);
    }
    
    // bikin HTML
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Data Bulanan</title>
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
            margin: 0;
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
            height: 3.77cm;
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
            margin-bottom: 3mm;
            line-height: 1.1;
        }

        .table-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
            table-layout: auto;
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

        .spacer {
            height: 5cm;
        }
    </style>
</head>
<body>';
    
    // KOP gambar
    if (!empty($kopBase64)) {
        $html .= '<table class="kop-table">
                    <tr>
                        <td>
                            <img src="data:image/jpeg;base64,' . $kopBase64 . '" alt="KOP">
                        </td>
                    </tr>
                  </table>
                  <div style="margin-bottom: 3mm;"></div>';
    }
    
    // judul sama subtitle
    $html .= '<p class="title">' . htmlspecialchars($title) . '</p>';
    $html .= '<p class="subtitle">Kota Pekanbaru, Provinsi Riau</p>';
    $html .= '<p class="period">Periode ' . htmlspecialchars($startFormatted) . ' - ' . htmlspecialchars($endFormatted) . '</p>';
    
    $html .= '<div class="page-wrapper">';
    
    // bikin tabel dengan max 28 baris per halaman
    $html .= '<div class="table-wrapper">';
    $html .= '<table>';
    $html .= '<thead><tr>';
    
    $html .= '<th class="date-col">Tanggal</th>';
    
    if (in_array('temperature', $variables)) {
        $html .= '<th>Rata-Rata Suhu<br>(°C)</th>';
    }
    
    if (in_array('humidity', $variables)) {
        $html .= '<th>Rata-Rata Kelembapan<br>(%)</th>';
    }
    
    if (in_array('wind', $variables)) {
        $html .= '<th>Kecepatan Angin<br>Max (m/s)</th>';
        $html .= '<th>Rata-Rata Kecepatan<br>Angin (m/s)</th>';
        $html .= '<th>Rata-Rata Arah<br>Angin</th>';
    }
    
    if (in_array('rainfall', $variables)) {
        $html .= '<th>Curah Hujan<br>(mm)</th>';
    }
    
    $html .= '</tr></thead>';
    $html .= '<tbody>';
    
    $rowCount = 0;
    $monthsMap = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    
    if (is_array($data) && !empty($data)) {
        foreach ($data as $row) {
            $html .= '<tr>';
            
            $tanggal = isset($row['tanggal']) ? $row['tanggal'] : '-';
            
            // kolom tanggal - format bulan sama tahun aja dari YYYY-MM-DD
            if ($tanggal !== '-') {
                $parts = explode('-', $tanggal);
                if (count($parts) === 3) {
                    $bulan = $parts[1];
                    $tahun = $parts[0];
                    $namaBulan = isset($monthsMap[$bulan]) ? $monthsMap[$bulan] : $bulan;
                    $tanggalDisplay = $namaBulan . ' ' . $tahun;
                } else {
                    $tanggalDisplay = $tanggal;
                }
            } else {
                $tanggalDisplay = '-';
            }
            $html .= '<td class="date-col">' . htmlspecialchars($tanggalDisplay) . '</td>';
            
            if (in_array('temperature', $variables)) {
                $tempAvg = isset($row['temp_avg']) && $row['temp_avg'] !== null ? number_format((float)$row['temp_avg'], 1) : '-';
                $html .= '<td>' . htmlspecialchars($tempAvg) . '</td>';
            }
            
            if (in_array('humidity', $variables)) {
                $humAvg = isset($row['humid_avg']) && $row['humid_avg'] !== null ? number_format((float)$row['humid_avg'], 1) : '-';
                $html .= '<td>' . htmlspecialchars($humAvg) . '</td>';
            }
            
            if (in_array('wind', $variables)) {
                $windSpeedMax = isset($row['wind_speed_max']) && $row['wind_speed_max'] !== null ? number_format((float)$row['wind_speed_max'], 1) : '-';
                $windSpeedAvg = isset($row['wind_speed_avg']) && $row['wind_speed_avg'] !== null ? number_format((float)$row['wind_speed_avg'], 1) : '-';
                $windDirAvg = isset($row['wind_dir_avg']) ? $row['wind_dir_avg'] : '-';
                $html .= '<td>' . htmlspecialchars($windSpeedMax) . '</td>';
                $html .= '<td>' . htmlspecialchars($windSpeedAvg) . '</td>';
                $html .= '<td>' . htmlspecialchars($windDirAvg) . '</td>';
            }
            
            if (in_array('rainfall', $variables)) {
                $rainfall = isset($row['rainfall']) && $row['rainfall'] !== null ? number_format((float)$row['rainfall'], 1) : '-';
                $html .= '<td>' . htmlspecialchars($rainfall) . '</td>';
            }
            
            $html .= '</tr>';
            $rowCount++;
        }
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';  // Close table-wrapper
    
    // tanda tangan di halaman yang sama dengan tabel (dalam page-wrapper)
    $today = new DateTime();
    $todayText = $today->format('d F Y');
    foreach ($months as $en => $id) {
        $todayText = str_replace($en, $id, $todayText);
    }
    
    $html .= '<div class="signature-section">
        <div class="signature-text">
            <p style="margin-bottom: 2px;">Pekanbaru, ' . htmlspecialchars($todayText) . '</p>
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
    
    // bikin PDF pake Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', false);
    $options->set('fontSubsettingEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // output PDF
    $startDateForFilename = str_replace('-', '', $startDate);
    $endDateForFilename = str_replace('-', '', $endDate);
    $variableString = implode('-', array_map(function($v) {
        return ['temperature' => 'suhu', 'humidity' => 'kelembapan', 'wind' => 'angin', 'rainfall' => 'hujan'][$v];
    }, $variables));
    
    $filename = 'laporan-bulan-' . $variableString . '-' . $startDateForFilename . '-' . $endDateForFilename . '.pdf';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dompdf->output();
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export PDF Bulanan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .form-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        a:hover {
            background-color: #0b7dda;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Export PDF Bulanan</h1>
        <p style="text-align: center; color: #666;">Gunakan form di halaman Data untuk membuat laporan</p>
        <p style="text-align: center;">
            <a href="Data.php">← Kembali ke Data</a>
        </p>
    </div>
</body>
</html>
