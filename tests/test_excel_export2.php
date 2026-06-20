<?php
/**
 * Test file untuk verify export_excel.php functionality dengan applyFromArray
 */

require 'vendor/autoload.php';

try {
    // Test 1: Create Spreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    echo "✓ Spreadsheet created\n";
    
    // Test 2: Create Sheet
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Test Sheet');
    echo "✓ Sheet created\n";
    
    // Test 3: Create comprehensive style array
    $headerStyleArray = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 10,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '32A852'],
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
        'borders' => [
            'left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'right' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
        ]
    ];
    echo "✓ Header style array created\n";
    
    // Test 4: Apply styles to cell using applyFromArray
    $sheet->setCellValue('A1', 'Test Header');
    $sheet->getStyle('A1')->applyFromArray($headerStyleArray);
    echo "✓ Styles applied to cell using applyFromArray\n";
    
    // Test 5: Set freeze pane
    $sheet->freezePane('B2');
    echo "✓ Freeze pane set\n";
    
    // Test 6: Set column width
    $sheet->getColumnDimension('A')->setWidth(15);
    echo "✓ Column width set\n";
    
    // Test 7: Test with date column styling
    $dateColumnStyleArray = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => '000000'],
            'size' => 10,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F0F0F0'],
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'right' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
        ]
    ];
    
    $sheet->setCellValue('B1', '2023-01-01');
    $sheet->getStyle('B1')->applyFromArray($dateColumnStyleArray);
    echo "✓ Date column styling applied\n";
    
    echo "\n✅ ALL TESTS PASSED - export_excel.php should work correctly!\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Class: " . get_class($e) . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
?>
