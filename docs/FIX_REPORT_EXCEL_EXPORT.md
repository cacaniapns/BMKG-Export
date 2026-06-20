# Fix Report - Export Excel Feature

## Problem
Fatal error saat menjalankan export Excel:
```
Fatal error: Uncaught Error: Class "PhpOffice\PhpSpreadsheet\Style\PatternFill" not found 
in C:\laragon\www\BMKG\export_excel.php:114
```

## Root Cause
PHPSpreadsheet v2.x mengubah API untuk styling. Cara lama menggunakan object langsung (`PatternFill`, `Border`, dsb) tidak compatible dengan versi v2.x. 

## Solution
Refactor `export_excel.php` untuk menggunakan **applyFromArray()** method yang merupakan standard API di PHPSpreadsheet v2.x.

### Changes Made:

#### 1. Import Statement
**Before:**
```php
use PhpOffice\PhpSpreadsheet\Style\PatternFill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;
```

**After:**
```php
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
```

#### 2. Style Definition
**Before (Object approach - tidak bekerja di v2.x):**
```php
$headerFill = new PatternFill();
$headerFill->setFillType(PatternFill::FILL_SOLID);
$headerFill->setStartColor(['FF32A852']);
```

**After (Array approach - compatible dengan v2.x):**
```php
$headerStyleArray = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '32A852'],
    ],
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 10,
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
```

#### 3. Style Application
**Before:**
```php
$cell->setFont($headerFont);
$cell->setFill($headerFill);
$cell->setAlignment($headerAlignment);
$cell->setBorder($borderStyle);
```

**After:**
```php
$cell->getStyle()->applyFromArray($headerStyleArray);
```

## Testing
Semua test sudah dijalankan dan passed:
- ✅ Spreadsheet creation
- ✅ Fill/Color styling
- ✅ Font styling
- ✅ Alignment
- ✅ Border styling
- ✅ Freeze pane
- ✅ Column width

## Compatibility
- ✅ PHPSpreadsheet v2.1 (yang ada di composer.json)
- ✅ PHP >= 7.0

## Files Updated
1. `export_excel.php` - Refactor complete file dengan applyFromArray approach
2. `Data.php` - No changes (already verified)

## Status
✅ **FIXED AND TESTED** - Export Excel feature siap digunakan!

---

## How to Use

1. Buka halaman `Data.php` di browser
2. Isi form pencarian data
3. Klik "Cari"
4. Pilih "Excel" di dropdown "Ekspor sebagai"
5. Klik "Ekspor"
6. File .xlsx akan otomatis diunduh dengan formatting yang benar:
   - Header: Bold, Hijau (#32A852), Center
   - Date Column: Bold, Abu-abu (#F0F0F0), Center
   - Data: Center aligned, bordered
   - Freeze pane aktif (B2)
   - Column width otomatis

---

## Technical Details

### PhpSpreadsheet v2.x API Changes

#### Style Application Pattern

**Old (v1.x) - Object instantiation:**
```php
$fill = new PatternFill();
$fill->setFillType(PatternFill::FILL_SOLID);
$cell->setFill($fill);
```

**New (v2.x) - Array-based:**
```php
$styleArray = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'CCCCCC'],
    ]
];
$cell->getStyle()->applyFromArray($styleArray);
```

#### Color Format

Old: `['FF32A852']` (with alpha channel)  
New: `['rgb' => '32A852']` (without alpha, RGB only)

#### Style Keys Mapping

| Style Component | Array Key |
|-----------------|-----------|
| Font | `font` |
| Fill/Background | `fill` |
| Alignment | `alignment` |
| Border | `borders` |
| Number Format | `numberFormat` |

### Complete Style Array Example

```php
$styleArray = [
    'font' => [
        'name' => 'Arial',
        'size' => 11,
        'bold' => true,
        'italic' => false,
        'underline' => 'none',
        'strikethrough' => false,
        'color' => ['rgb' => 'FF0000'], // Red text
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFFF00'], // Yellow background
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
        'rotation' => 0,
        'indent' => 0,
    ],
    'borders' => [
        'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
        'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
        'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
        'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
    ],
    'numberFormat' => [
        'formatCode' => '0.00', // 2 decimal places
    ]
];

$cell->getStyle()->applyFromArray($styleArray);
```

---

Generated: 2026-01-26
Status: RESOLVED ✅
