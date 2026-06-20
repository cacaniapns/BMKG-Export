<?php
date_default_timezone_set('Asia/Jakarta');

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// validasi request POST
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['data']) || !isset($_POST['time_type'])) {
    die('Invalid request');
}

$timeType = $_POST['time_type'];
$data = unserialize(urldecode($_POST['data']));
$variables = isset($_POST['variables']) ? explode(',', $_POST['variables']) : [];
$variables = array_map('trim', array_filter($variables));
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : 'N/A';
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : 'N/A';

// pastiin variabel yang dipilih udah valid
$validVariables = ['temperature', 'humidity', 'wind', 'rainfall'];
$variables = array_intersect($variables, $validVariables);

if (empty($variables) || empty($data)) {
    die('Tidak ada data yang dipilih untuk export Excel.');
}

// ubah nomor kolom jadi huruf
function getColumnLetter($col) {
    $col = intval($col);
    if ($col <= 0) return 'A';
    if ($col <= 26) return chr(64 + $col);
    return chr(64 + intdiv($col - 1, 26)) . chr(65 + (($col - 1) % 26));
}

// ubah derajat angin jadi arah mata angin
function degToCompass($deg) {
    // data arah angin bisa berupa kode (N, NE, E, SE, S, SW, W, NW, atau Calm)
    if ($deg === null || $deg === '') return '-';
    
    // kalo udah kode arah, langsung return aja
    $compassCodes = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW', 'Calm'];
    if (in_array($deg, $compassCodes)) {
        return $deg;
    }
    
    // kalo angka derajat, konversi ke arah
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

// bikin spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Export');

// styling header dan data
$headerStyleArray = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '32A852']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '000000']]]
];

$dateColumnStyleArray = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '000000']]]
];

$dataCellStyleArray = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '000000']]]
];

// susun header kolom
$headers = ['Tanggal'];
foreach ($variables as $var) {
    if ($var === 'rainfall') {
        $headers[] = 'Curah Hujan (mm)';
    } elseif ($var === 'temperature') {
        if ($timeType === 'hourly') {
            for ($h = 0; $h < 24; $h++) $headers[] = sprintf('Suhu %02d:00', $h);
        } else {
            $headers[] = 'Suhu Max (°C)';
            $headers[] = 'Suhu Min (°C)';
            $headers[] = 'Rata-Rata Suhu (°C)';
        }
    } elseif ($var === 'humidity') {
        if ($timeType === 'hourly') {
            for ($h = 0; $h < 24; $h++) $headers[] = sprintf('Kelembapan %02d:00', $h);
        } else {
            $headers[] = 'Kelembapan Max (%)';
            $headers[] = 'Kelembapan Min (%)';
            $headers[] = 'Rata-Rata Kelembapan (%)';
        }
    } elseif ($var === 'wind') {
        if ($timeType === 'hourly') {
            for ($h = 0; $h < 24; $h++) {
                $headers[] = sprintf('Angin Kec %02d:00', $h);
                $headers[] = sprintf('Angin Arah %02d:00', $h);
            }
        } else {
            $headers[] = 'Kecepatan Angin Max (m/s)';
            $headers[] = 'Rata-Rata Kecepatan Angin (m/s)';
            $headers[] = 'Rata-Rata Arah Angin';
        }
    }
}

// tulis header ke sheet
foreach ($headers as $col => $header) {
    $cellRef = getColumnLetter($col + 1) . '1';
    $sheet->getCell($cellRef)->setValue($header);
    $sheet->getCell($cellRef)->getStyle()->applyFromArray($headerStyleArray);
}

// isi data ke sheet
$rowNum = 2;
foreach ($data as $row) {
    $tanggal = $row['tanggal'] ?? '';
    
    // format tanggal buat tampilan bulanan
    if ($timeType === 'monthly' && $tanggal !== '') {
        $parts = explode('-', $tanggal);
        if (count($parts) === 3) {
            $bulan = $parts[1];
            $tahun = $parts[0];
            $monthNames = [
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            ];
            $namaBulan = $monthNames[$bulan] ?? $bulan;
            $tanggal = $namaBulan . ' ' . $tahun;
        }
    }
    
    $sheet->getCell('A' . $rowNum)->setValue($tanggal);
    $sheet->getCell('A' . $rowNum)->getStyle()->applyFromArray($dateColumnStyleArray);
    
    // tulis data sesuai variabel yang dipilih
    $col = 2;
    foreach ($variables as $var) {
        if ($var === 'rainfall') {
            $value = $row['rainfall'] ?? '';
            if ($value === 8888 || $value == '8888') $value = '-';
            else $value = $value ? round($value, 1) : '-';
            
            $cellRef = getColumnLetter($col) . $rowNum;
            $sheet->getCell($cellRef)->setValue($value);
            $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
            $col++;
            
        } elseif ($var === 'temperature') {
            if ($timeType === 'hourly') {
                for ($h = 0; $h < 24; $h++) {
                    $value = $row["temp_$h"] ?? '';
                    $cellRef = getColumnLetter($col) . $rowNum;
                    $sheet->getCell($cellRef)->setValue($value ? round($value, 1) : '-');
                    $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
                    $col++;
                }
            } else {
                foreach (['temp_max', 'temp_min', 'temp_avg'] as $key) {
                    $value = $row[$key] ?? '';
                    $value = $value ? round($value, 1) : '-';
                    $cellRef = getColumnLetter($col) . $rowNum;
                    $sheet->getCell($cellRef)->setValue($value);
                    $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
                    $col++;
                }
            }
            
        } elseif ($var === 'humidity') {
            if ($timeType === 'hourly') {
                for ($h = 0; $h < 24; $h++) {
                    $value = $row["humid_$h"] ?? '';
                    $cellRef = getColumnLetter($col) . $rowNum;
                    $sheet->getCell($cellRef)->setValue($value ? round($value, 1) : '-');
                    $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
                    $col++;
                }
            } else {
                foreach (['humid_max', 'humid_min', 'humid_avg'] as $key) {
                    $value = $row[$key] ?? '';
                    $value = $value ? round($value, 1) : '-';
                    $cellRef = getColumnLetter($col) . $rowNum;
                    $sheet->getCell($cellRef)->setValue($value);
                    $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
                    $col++;
                }
            }
            
        } elseif ($var === 'wind') {
            if ($timeType === 'hourly') {
                for ($h = 0; $h < 24; $h++) {
                    // kecepatan angin
                    $value = $row["wind_speed_$h"] ?? '';
                    if ($value === '' || $value === 8888 || $value == '8888') {
                        $value = '-';
                    } else {
                        $value = round($value, 1);
                    }
                    $cellRef = getColumnLetter($col) . $rowNum;
                    $sheet->getCell($cellRef)->setValue($value);
                    $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
                    $col++;
                    
                    // arah angin
                    $value = $row["wind_dir_$h"] ?? '';
                    $value = $value ? degToCompass($value) : '-';
                    $cellRef = getColumnLetter($col) . $rowNum;
                    $sheet->getCell($cellRef)->setValue($value);
                    $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
                    $col++;
                }
            } else {
                // harian/bulanan: kecepatan angin max, rata-rata, arah rata-rata
                foreach (['wind_speed_max', 'wind_speed_avg'] as $key) {
                    $value = $row[$key] ?? '';
                    if ($value === '' || $value === 8888 || $value == '8888') {
                        $value = '-';
                    } else {
                        $value = round($value, 1);
                    }
                    $cellRef = getColumnLetter($col) . $rowNum;
                    $sheet->getCell($cellRef)->setValue($value);
                    $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
                    $col++;
                }
                
                $value = $row['wind_dir_avg'] ?? '';
                $value = $value ? $value : '-';
                $cellRef = getColumnLetter($col) . $rowNum;
                $sheet->getCell($cellRef)->setValue($value);
                $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
                $col++;
            }
        }
    }
    
    $rowNum++;
}

// otomatis atur lebar kolom
foreach (range(1, count($headers)) as $col) {
    $colLetter = getColumnLetter($col);
    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
}

// freeze baris dan kolom pertama
$sheet->freezePane('B2');

// bikin nama file
$filename = 'export-' . $startDate . '-' . $endDate . '-' . date('Y-m-d-H-i-s') . '.xlsx';

// set header buat download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// output file ke browser
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit;
