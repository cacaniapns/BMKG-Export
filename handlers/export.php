<?php
require 'vendor/autoload.php'; // Pastikan path ini benar

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Fungsi untuk menghasilkan file PDF (menggunakan dompdf atau fallback Word)
function generatePDF($data, $dataType) {
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    $section->addTitle('Data ' . ucfirst($dataType), 1);

    $columns = [];
    if ($dataType === 'temperature') {
        $columns = ['Tanggal', 'Bulan', 'Tahun', 'Max Temperature (°C)', 'Min Temperature (°C)', 'Avg Temperature (°C)'];
    } elseif ($dataType === 'humidity') {
        $columns = ['Tanggal', 'Bulan', 'Tahun', 'Max Humidity (%)', 'Min Humidity (%)', 'Avg Humidity (%)'];
    } elseif ($dataType === 'wind') {
        $columns = ['Tanggal', 'Bulan', 'Tahun', 'WS_Max', 'WD_Max', 'WS_Rata2', 'WD_Rata2'];
    } else {
        $columns = ['Tanggal', 'Bulan', 'Tahun', 'Curah Hujan (mm)'];
    }

    $tableStyle = [
        'borderSize' => 6,
        'cellMargin' => 80,
        'alignment' => 'center',
        'borderColor' => '000000',
        'bgColor' => 'f2f2f2'
    ];
    $phpWord->addTableStyle('myTable', $tableStyle);
    $table = $section->addTable('myTable');

    // Header table
    $table->addRow();
    foreach ($columns as $column) {
        $table->addCell(2000, ['bgColor' => '009879'])->addText($column, ['bold' => true, 'color' => 'FFFFFF']);
    }

    // Data rows
    foreach ($data as $row) {
        $table->addRow();
        foreach ($row as $cell) {
            $table->addCell(2000)->addText($cell);
        }
    }

    $filename = 'data_' . $dataType . '.pdf';
    
    // Try to use dompdf for PDF conversion
    try {
        // Generate HTML from PhpWord
        $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
        ob_start();
        $htmlWriter->save('php://output');
        $html = ob_get_clean();
        
        // Use dompdf to convert HTML to PDF
        $dompdf = new \Dompdf\Dompdf([
            'enable_remote' => false,
            'isHtml5ParserEnabled' => true,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        echo $dompdf->output();
        exit;
    } catch (Exception $e) {
        // Fallback: Output as DOCX if PDF generation fails
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment;filename="data_' . $dataType . '.docx"');
        header('Cache-Control: max-age=0');
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save('php://output');
        exit;
    }
}

// Fungsi untuk menghasilkan file Word
function generateWord($data, $dataType) {
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    $section->addTitle('Data ' . ucfirst($dataType), 1);

    $columns = [];
    if ($dataType === 'temperature') {
        $columns = ['Tanggal', 'Bulan', 'Tahun', 'Max Temperature (°C)', 'Min Temperature (°C)', 'Avg Temperature (°C)'];
    } elseif ($dataType === 'humidity') {
        $columns = ['Tanggal', 'Bulan', 'Tahun', 'Max Humidity (%)', 'Min Humidity (%)', 'Avg Humidity (%)'];
    } elseif ($dataType === 'wind') {
        $columns = ['Tanggal', 'Bulan', 'Tahun', 'WS_Max', 'WD_Max', 'WS_Rata2', 'WD_Rata2'];
    } else {
        $columns = ['Tanggal', 'Bulan', 'Tahun', 'Curah Hujan (mm)'];
    }

    $tableStyle = [
        'borderSize' => 6,
        'cellMargin' => 80,
        'alignment' => 'center',
        'borderColor' => '000000',
        'bgColor' => 'f2f2f2'
    ];
    $phpWord->addTableStyle('myTable', $tableStyle);
    $table = $section->addTable('myTable');

    // Header table
    $table->addRow();
    foreach ($columns as $column) {
        $table->addCell(2000, ['bgColor' => '009879'])->addText($column, ['bold' => true, 'color' => 'FFFFFF']);
    }

    // Data rows
    foreach ($data as $row) {
        $table->addRow();
        foreach ($row as $cell) {
            $table->addCell(2000)->addText($cell);
        }
    }

    $filename = 'data_' . $dataType . '.docx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
}

// Fungsi untuk menghasilkan file Excel
function generateExcel($data, $dataType) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $columns = [];
    if ($dataType === 'temperature') {
        $columns = ['Tanggal', 'Bulan', 'Tahun', 'Max Temperature (°C)', 'Min Temperature (°C)', 'Avg Temperature (°C)'];
    } elseif ($dataType === 'humidity') {
        $columns = ['Tanggal', 'Bulan', 'Tahun', 'Max Humidity (%)', 'Min Humidity (%)', 'Avg Humidity (%)'];
    } elseif ($dataType === 'wind') {
        $columns = ['Tanggal', 'Bulan', 'Tahun', 'WS_Max', 'WD_Max', 'WS_Rata2', 'WD_Rata2'];
    } else {
        $columns = ['Tanggal', 'Bulan', 'Tahun', 'Curah Hujan (mm)'];
    }

    // Terapkan gaya header
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '4CAF50']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ];
    $sheet->getStyle('A1:' . chr(64 + count($columns)) . '1')->applyFromArray($headerStyle);

    // Set header
    $colIndex = 'A';
    foreach ($columns as $column) {
        $sheet->setCellValue($colIndex . '1', $column);
        $colIndex++;
    }

    // Set data
    $rowNumber = 2;
    foreach ($data as $row) {
        $colIndex = 'A';
        foreach ($row as $cell) {
            $sheet->setCellValue($colIndex . $rowNumber, $cell);
            $colIndex++;
        }
        $rowNumber++;
    }

    // Auto-size columns
    foreach (range('A', chr(64 + count($columns))) as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Output file
    $writer = new Xlsx($spreadsheet);
    $filename = 'data_' . $dataType . '.xlsx';

    ob_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1'); // For IE 9

    $writer->save('php://output');
}

// Periksa apakah request menggunakan metode POST dan parameter data ada
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['data']) && isset($_POST['export'])) {
    $data = unserialize(urldecode($_POST['data']));
    $exportType = $_POST['export'];
    
    // Tentukan data type dari variables (gunakan yang pertama jika ada multiple)
    $variables = isset($_POST['variables']) ? trim($_POST['variables']) : '';
    $variableList = array_filter(array_map('trim', explode(',', $variables)));
    $dataType = !empty($variableList) ? $variableList[0] : 'temperature';

    switch ($exportType) {
        case 'pdf':
            generatePDF($data, $dataType);
            break;
        case 'word':
            generateWord($data, $dataType);
            break;
        case 'excel':
            generateExcel($data, $dataType);
            break;
        default:
            echo 'Format tidak dikenali.';
            break;
    }
} else {
    echo 'Data atau format ekspor tidak ditemukan.';
}
?>
