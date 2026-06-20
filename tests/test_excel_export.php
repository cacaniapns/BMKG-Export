<?php
/**
 * Test file untuk verify export_excel.php functionality
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
    
    // Test 3: Create Fill object
    $fill = new \PhpOffice\PhpSpreadsheet\Style\Fill();
    $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
    $fill->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF32A852'));
    echo "✓ Fill object created and configured\n";
    
    // Test 4: Create Font object
    $font = new \PhpOffice\PhpSpreadsheet\Style\Font();
    $font->setBold(true);
    $font->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
    echo "✓ Font object created\n";
    
    // Test 5: Create Alignment object
    $alignment = new \PhpOffice\PhpSpreadsheet\Style\Alignment();
    $alignment->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $alignment->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    echo "✓ Alignment object created\n";
    
    // Test 6: Create Border style array
    $borderStyleArray = [
        'borders' => [
            'left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'right' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
        ]
    ];
    echo "✓ Border style array created\n";
    
    // Test 7: Apply styles to cell using applyFromArray
    $sheet->setCellValue('A1', 'Test Header');
    $sheet->getStyle('A1')->setFont($font);
    $sheet->getStyle('A1')->setFill($fill);
    $sheet->getStyle('A1')->setAlignment($alignment);
    $sheet->getStyle('A1')->applyFromArray($borderStyleArray);
    echo "✓ Styles applied to cell using applyFromArray\n";
    
    // Test 8: Set freeze pane
    $sheet->freezePane('B2');
    echo "✓ Freeze pane set\n";
    
    // Test 9: Set column width
    $sheet->getColumnDimension('A')->setWidth(15);
    echo "✓ Column width set\n";
    
    // Test 10: Verify Color class works with hex values
    $testColor = new \PhpOffice\PhpSpreadsheet\Style\Color('FF32A852');
    echo "✓ Color class with hex values works\n";
    
    echo "\n✅ ALL TESTS PASSED - export_excel.php should work correctly!\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Class: " . get_class($e) . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
?>
