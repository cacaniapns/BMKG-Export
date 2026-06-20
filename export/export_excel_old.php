<?php
/**
 * Export data ke Excel (.xlsx)
 * 
 * Format:
 * - 1 file Excel dengan banyak sheet
 * - 1 sheet per variabel (Curah Hujan, Suhu, Kelembapan, Angin_Arah, Angin_Kecepatan)
 * - Struktur: Kolom A (Tanggal), Kolom B-Y (Jam 00:00 sampai 23:00)
 * - 1 baris = 1 tanggal
 * - Header: Bold, Background Hijau, Text Center
 * - Kolom Tanggal: Bold, Background Abu-abu
 * - Freeze panes: Header + Kolom tanggal
 * - Auto-adjust column width
 * - Data kosong: "-"
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['data']) || !isset($_POST['time_type'])) {
    die('Invalid request');
}

$timeType = $_POST['time_type'];
$data = unserialize(urldecode($_POST['data']));
$variables = isset($_POST['variables']) ? explode(',', $_POST['variables']) : [];
$variables = array_map('trim', array_filter($variables));
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : 'N/A';
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : 'N/A';

// validasi variabel
$validVariables = ['temperature', 'humidity', 'wind', 'rainfall'];
$variables = array_intersect($variables, $validVariables);

if (empty($variables) || empty($data)) {
    die('Tidak ada data yang dipilih untuk export Excel.');
}

// supaya urutan sheet selalu sama: hujan, suhu, kelembapan, arah angin, kec angin
$orderedVariables = [];
if (in_array('rainfall', $variables)) $orderedVariables[] = 'rainfall';
if (in_array('temperature', $variables)) $orderedVariables[] = 'temperature';
if (in_array('humidity', $variables)) $orderedVariables[] = 'humidity';
if (in_array('wind', $variables)) {
    $orderedVariables[] = 'wind_direction';
    $orderedVariables[] = 'wind_speed';
}
$variables = $orderedVariables;

// mapping nama sheet
$sheetNames = [
    'rainfall' => 'Curah Hujan',
    'temperature' => 'Suhu',
    'humidity' => 'Kelembapan',
    'wind_direction' => 'Angin_Arah',
    'wind_speed' => 'Angin_Kecepatan'
];

// fungsi buat ubah nomor kolom jadi huruf (1=A, 2=B, dst)
function getColumnLetter($col) {
    $col = intval($col);
    if ($col <= 0) return 'A';
    if ($col <= 26) return chr(64 + $col);
    return chr(64 + intdiv($col - 1, 26)) . chr(65 + (($col - 1) % 26));
}

// fungsi buat ubah derajat jadi arah mata angin 8 titik
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

// bikin spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Export');

// definisi style array
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

// susun header
$headers = ['Tanggal'];
foreach ($variables as $var) {
    if ($var === 'rainfall') {
        $headers[] = 'Curah Hujan (mm)';
    } elseif ($var === 'temperature') {
        if ($timeType === 'hourly') {
            for ($h = 0; $h < 24; $h++) $headers[] = sprintf('%02d:00', $h);
        } else {
            $headers[] = 'Suhu Max (°C)';
            $headers[] = 'Suhu Min (°C)';
            $headers[] = 'Rata-Rata Suhu (°C)';
        }
    } elseif ($var === 'humidity') {
        if ($timeType === 'hourly') {
            for ($h = 0; $h < 24; $h++) $headers[] = sprintf('H%02d', $h);
        } else {
            $headers[] = 'Kelembapan Max (%)';
            $headers[] = 'Kelembapan Min (%)';
            $headers[] = 'Rata-Rata Kelembapan (%)';
        }
    } elseif ($var === 'wind_direction') {
        if ($timeType !== 'hourly') {
            $headers[] = 'Arah Angin';
            $headers[] = 'Rata-Rata Arah Angin';
        }
    } elseif ($var === 'wind_speed') {
        if ($timeType === 'hourly') {
            for ($h = 0; $h < 24; $h++) $headers[] = sprintf('W%02d', $h);
        } else {
            $headers[] = 'Kecepatan Angin Max (m/s)';
            $headers[] = 'Rata-Rata Kecepatan Angin (m/s)';
        }
    }
}

// tulis header
foreach ($headers as $col => $header) {
    $cellRef = getColumnLetter($col + 1) . '1';
    $sheet->getCell($cellRef)->setValue($header);
    $sheet->getCell($cellRef)->getStyle()->applyFromArray($headerStyleArray);
}

// isi data
$rowNum = 2;
foreach ($data as $row) {
    $tanggal = $row['tanggal'] ?? '';
    
    // format tanggal buat monthly
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
    
    // isi data sesuai variabel
    $col = 2;
    foreach ($variables as $var) {
        if ($var === 'rainfall') {
            $value = $row['rainfall'] ?? '';
            if ($value === 8888 || $value == '8888') $value = '-';
            else $value = round($value, 1);
            
            $cellRef = getColumnLetter($col) . $rowNum;
            $sheet->getCell($cellRef)->setValue($value);
            $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
            $col++;
        } elseif ($var === 'temperature') {
            if ($timeType === 'hourly') {
                for ($h = 0; $h < 24; $h++) {
                    $value = $row["temp_$h"] ?? '';
                    $cellRef = getColumnLetter($col) . $rowNum;
                    $sheet->getCell($cellRef)->setValue($value ?: '-');
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
                    $sheet->getCell($cellRef)->setValue($value ?: '-');
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
        } elseif ($var === 'wind_direction') {
            if ($timeType !== 'hourly') {
                $value = $row['wind_dir_max'] ?? '';
                $value = $value ? degToCompass($value) : '-';
                $cellRef = getColumnLetter($col) . $rowNum;
                $sheet->getCell($cellRef)->setValue($value);
                $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
                $col++;
                
                $value = $row['wind_dir_avg'] ?? '';
                $value = $value ? degToCompass($value) : '-';
                $cellRef = getColumnLetter($col) . $rowNum;
                $sheet->getCell($cellRef)->setValue($value);
                $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
                $col++;
            }
        } elseif ($var === 'wind_speed') {
            if ($timeType === 'hourly') {
                for ($h = 0; $h < 24; $h++) {
                    $value = $row["wind_$h"] ?? '';
                    $cellRef = getColumnLetter($col) . $rowNum;
                    $sheet->getCell($cellRef)->setValue($value ?: '-');
                    $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
                    $col++;
                }
            } else {
                foreach (['wind_speed_max', 'wind_speed_avg'] as $key) {
                    $value = $row[$key] ?? '';
                    $value = $value ? round($value, 1) : '-';
                    $cellRef = getColumnLetter($col) . $rowNum;
                    $sheet->getCell($cellRef)->setValue($value);
                    $sheet->getCell($cellRef)->getStyle()->applyFromArray($dataCellStyleArray);
                    $col++;
                }
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

// freeze baris sama kolom pertama
$sheet->freezePane('B2');
    
    // definisi style array
    $headerStyleArray = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 10,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '32A852'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
        ]
    ];
    
    $dateStyleArray = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => '000000'],
            'size' => 10,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F0F0F0'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
        ]
    ];
    
    $dataStyleArray = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
        ]
    ];
    
    // bikin header sesuai tipe waktu
    $sheet->setCellValue('A1', 'Tanggal');
    
    if ($timeType === 'hourly') {
        // header buat jam 00:00 - 23:00
        for ($h = 0; $h < 24; $h++) {
            $col = chr(66 + $h); // B = 66
            $sheet->setCellValue($col . '1', sprintf('%02d:00', $h));
        }
    } else {
        // buat daily/monthly, header sesuai tipe variabel
        if ($variableName === 'temperature') {
            $sheet->setCellValue('B1', 'Suhu Max (°C)');
            $sheet->setCellValue('C1', 'Suhu Min (°C)');
            $sheet->setCellValue('D1', 'Rata-Rata Suhu (°C)');
        } elseif ($variableName === 'humidity') {
            $sheet->setCellValue('B1', 'Kelembapan Max (%)');
            $sheet->setCellValue('C1', 'Kelembapan Min (%)');
            $sheet->setCellValue('D1', 'Rata-Rata Kelembapan (%)');
        } elseif ($variableName === 'wind_speed') {
            $sheet->setCellValue('B1', 'Kecepatan Angin Max (m/s)');
            $sheet->setCellValue('C1', 'Rata-Rata Kecepatan Angin (m/s)');
        } elseif ($variableName === 'wind_direction') {
            $sheet->setCellValue('B1', 'Arah Angin Max');
            $sheet->setCellValue('C1', 'Rata-Rata Arah Angin');
        } elseif ($variableName === 'rainfall') {
            $sheet->setCellValue('B1', 'Curah Hujan (mm)');
        }
    }
    
    // terapkan styling header
    if ($timeType === 'hourly') {
        for ($col = 1; $col <= 25; $col++) {
            $colLetter = getColumnLetter($col);
            $cell = $sheet->getCell($colLetter . '1');
            $cell->getStyle()->applyFromArray($headerStyleArray);
        }
    } else {
        $maxCol = 4;
        if ($variableName === 'wind_speed' || $variableName === 'wind_direction') {
            $maxCol = 3;
        } elseif ($variableName === 'rainfall') {
            $maxCol = 2;
        }
        for ($col = 1; $col <= $maxCol; $col++) {
            $colLetter = getColumnLetter($col);
            $cell = $sheet->getCell($colLetter . '1');
            $cell->getStyle()->applyFromArray($headerStyleArray);
        }
    }
    
    // isi data
    $rowNum = 2;
    foreach ($data as $row) {
        $tanggal = $row['tanggal'] ?? '';
        
        // format tanggal buat tampilan monthly
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
        
        // kolom tanggal
        $cellRef = 'A' . $rowNum;
        $cell = $sheet->getCell($cellRef);
        $cell->setValue($tanggal);
        $cell->getStyle()->applyFromArray($dateStyleArray);
        
        // isi kolom jam (buat hourly)
        if ($timeType === 'hourly') {
            for ($h = 0; $h < 24; $h++) {
                $col = chr(66 + $h); // B = 66
                
                $key = '';
                $value = '-';
                
                if ($variableName === 'temperature') {
                    $key = 'temp_' . $h;
                } elseif ($variableName === 'humidity') {
                    $key = 'humid_' . $h;
                } elseif ($variableName === 'wind_speed') {
                    $key = 'wind_speed_' . $h;
                } elseif ($variableName === 'wind_direction') {
                    $key = 'wind_dir_' . $h;
                    // ubah arah angin kalo ada
                    if (isset($row[$key]) && $row[$key] !== null) {
                        $raw = $row[$key];
                        if (is_numeric($raw)) {
                            $value = degToCompass((float)$raw);
                        } elseif (preg_match('/([0-9]+(?:\\.[0-9]+)?)/', $raw, $m)) {
                            $value = degToCompass((float)$m[1]);
                        } else {
                            $value = strtoupper(trim((string)$raw));
                        }
                    }
                } elseif ($variableName === 'rainfall') {
                    $value = isset($row['rainfall']) && $row['rainfall'] !== null ? round((float)$row['rainfall'], 1) : '-';
                    // handle kode missing data (8888 = missing data)
                    if ($value == 8888 || $value === 8888.0) {
                        $value = '-';
                    }
                }
                
                if (in_array($variableName, ['temperature', 'humidity', 'wind_speed'])) {
                    $value = isset($row[$key]) && $row[$key] !== null ? round((float)$row[$key], 1) : '-';
                }
                
                $colLetter = getColumnLetter($h + 2);
                $cellRef = $colLetter . $rowNum;
                $cell = $sheet->getCell($cellRef);
                $cell->setValue($value);
                $cell->getStyle()->applyFromArray($dataStyleArray);
            }
        } else {
            // buat daily/monthly, tampilkan nilai agregat
            $colIndex = 2;
            if ($variableName === 'temperature') {
                $values = [
                    isset($row['temp_max']) && $row['temp_max'] !== null ? round((float)$row['temp_max'], 1) : '-',
                    isset($row['temp_min']) && $row['temp_min'] !== null ? round((float)$row['temp_min'], 1) : '-',
                    isset($row['temp_avg']) && $row['temp_avg'] !== null ? round((float)$row['temp_avg'], 1) : '-'
                ];
            } elseif ($variableName === 'humidity') {
                $values = [
                    isset($row['humid_max']) && $row['humid_max'] !== null ? round((float)$row['humid_max'], 1) : '-',
                    isset($row['humid_min']) && $row['humid_min'] !== null ? round((float)$row['humid_min'], 1) : '-',
                    isset($row['humid_avg']) && $row['humid_avg'] !== null ? round((float)$row['humid_avg'], 1) : '-'
                ];
            } elseif ($variableName === 'wind_speed') {
                $values = [
                    isset($row['wind_speed_max']) && $row['wind_speed_max'] !== null ? round((float)$row['wind_speed_max'], 1) : '-',
                    isset($row['wind_speed_avg']) && $row['wind_speed_avg'] !== null ? round((float)$row['wind_speed_avg'], 1) : '-'
                ];
            } elseif ($variableName === 'wind_direction') {
                $windDirMax = isset($row['wind_dir_max']) && $row['wind_dir_max'] !== null ? $row['wind_dir_max'] : '';
                $windDirAvg = isset($row['wind_dir_avg']) && $row['wind_dir_avg'] !== null ? $row['wind_dir_avg'] : '';
                
                // Convert wind direction to compass
                $windDirMaxCompass = '-';
                if ($windDirMax !== '') {
                    if (is_numeric($windDirMax)) {
                        $windDirMaxCompass = degToCompass((float)$windDirMax);
                    } elseif (preg_match('/([0-9]+(?:\\.[0-9]+)?)/', $windDirMax, $m)) {
                        $windDirMaxCompass = degToCompass((float)$m[1]);
                    } else {
                        $windDirMaxCompass = strtoupper(trim((string)$windDirMax));
                    }
                }
                
                $windDirAvgCompass = '-';
                if ($windDirAvg !== '') {
                    if (is_numeric($windDirAvg)) {
                        $windDirAvgCompass = degToCompass((float)$windDirAvg);
                    } elseif (preg_match('/([0-9]+(?:\\.[0-9]+)?)/', $windDirAvg, $m)) {
                        $windDirAvgCompass = degToCompass((float)$m[1]);
                    } else {
                        $windDirAvgCompass = strtoupper(trim((string)$windDirAvg));
                    }
                }
                
                $values = [$windDirMaxCompass, $windDirAvgCompass];
            } elseif ($variableName === 'rainfall') {
                $values = [
                    isset($row['rainfall']) && $row['rainfall'] !== null ? round((float)$row['rainfall'], 1) : '-'
                ];
            } else {
                $values = [];
            }
            
            // isi nilai ke kolom
            foreach ($values as $value) {
                // handle kode missing data (8888 = missing data di meteorologi)
                if ($value === 8888 || $value === '8888') {
                    $displayValue = '-';
                } else {
                    $displayValue = $value;
                }
                
                $colLetter = getColumnLetter($colIndex);
                $cellRef = $colLetter . $rowNum;
                $cell = $sheet->getCell($cellRef);
                $cell->setValue($displayValue);
                $cell->getStyle()->applyFromArray($dataStyleArray);
                $colIndex++;
            }
        }
        
        $rowNum++;
    }
    
    // otomatis atur lebar kolom
    $sheet->getColumnDimension('A')->setWidth(15);
    
    if ($timeType === 'hourly') {
        for ($h = 0; $h < 24; $h++) {
            $col = chr(66 + $h);
            $sheet->getColumnDimension($col)->setWidth(12);
        }
    } else {
        // buat daily/monthly
        if ($variableName === 'temperature' || $variableName === 'humidity') {
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(18);
            $sheet->getColumnDimension('D')->setWidth(18);
        } elseif ($variableName === 'wind_speed' || $variableName === 'wind_direction') {
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(18);
        } elseif ($variableName === 'rainfall') {
            $sheet->getColumnDimension('B')->setWidth(18);
        }
    }
    
    // freeze panes: freeze header (row 1) sama date column (column A)
    $sheet->freezePane('B2');

// bikin nama file
$filename = 'export-' . $startDate . '-' . $endDate . '-' . date('Y-m-d-H-i-s') . '.xlsx';

// set header buat download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// output file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit;
?>
