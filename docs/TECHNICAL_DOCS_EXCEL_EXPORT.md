# Dokumentasi Teknis - Export Excel Feature

## Ringkasan Implementasi

### 1. File: `export_excel.php`
File ini menangani semua logika untuk generate dan download file Excel.

#### Flow:
1. **Validasi Input**
   - Menerima POST request dari Data.php
   - Validasi time_type (hourly/daily/monthly)
   - Validasi variabel data (rainfall, temperature, humidity, wind)
   - Deserialize data yang sudah di-serialize dari Data.php

2. **Pemrosesan Data**
   - Urutkan variabel sesuai urutan yang ditentukan
   - Map nama sheet ke variabel
   - Create spreadsheet baru (hapus sheet default)

3. **Untuk Setiap Variabel:**
   - Panggil fungsi `createVariableSheet()`
   - Generate sheet dengan nama yang sesuai
   - Apply formatting (header, date column, borders, etc.)
   - Isi data sesuai time_type (hourly vs daily/monthly)
   - Set column width dan freeze panes

4. **Generate File**
   - Set HTTP headers untuk download
   - Write file ke output stream
   - Exit

#### Fungsi Utama: `createVariableSheet()`

```php
createVariableSheet($spreadsheet, $variableName, $sheetName, $data, $timeType)
```

**Parameters:**
- `$spreadsheet` - PHPSpreadsheet Spreadsheet object
- `$variableName` - Nama variabel (temperature, humidity, wind_speed, wind_direction, rainfall)
- `$sheetName` - Nama sheet yang akan ditampilkan di Excel
- `$data` - Array data dari database
- `$timeType` - Tipe waktu (hourly, daily, monthly)

**Proses:**
1. Create sheet baru
2. Setup styling untuk header (hijau, bold, center)
3. Setup styling untuk date column (abu-abu, bold)
4. Generate header row:
   - Hourly: Kolom A (Tanggal) + Kolom B-Y (Jam 00:00-23:00)
   - Daily/Monthly: Kolom A (Tanggal) + Kolom B-D (Max/Min/Avg atau sesuai variabel)
5. Isi data dengan looping:
   - Hourly: Loop 24 jam per baris
   - Daily/Monthly: Loop nilai agregat per baris
6. Apply styling untuk setiap cell (border, alignment)
7. Auto-adjust column width
8. Set freeze pane pada B2 (freeze header + date column)

#### Konversi Wind Direction:
Fungsi `degToCompass()` mengkonversi derajat ke 8-point compass:
- 337.5 - 22.5 = N (North)
- 22.5 - 67.5 = NE (Northeast)
- 67.5 - 112.5 = E (East)
- dst...

Data wind direction dari database bisa dalam format:
- String numeric (e.g., "45.5")
- String dengan degree symbol (e.g., "45.5°")
- Compass code (e.g., "N", "NE")

Semua format akan diproses dan dikonversi ke 8-point compass.

---

### 2. File: `Data.php` (Modified)

#### Perubahan:
1. **Dropdown Export Format**
   - Tambah option "Excel" untuk hourly dan daily
   - Monthly tetap hanya PDF

2. **Form Attribute**
   - Tambah ID `id="export-form"`
   - Tambah event handler `onchange="updateExportAction(this.value)"`

3. **JavaScript Function: `updateExportAction()`**
   ```javascript
   function updateExportAction(format) {
     var form = document.getElementById("export-form");
     var timeType = "..."; // dari PHP
     
     if (format === "excel") {
       form.action = "export_excel.php";
     } else if (format === "pdf") {
       // Set action berdasarkan timeType
       // export_hourly_pdf.php / export_daily_pdf.php / export_monthly_pdf.php
     } else if (format === "word") {
       form.action = "export.php";
     }
   }
   ```

   **Fungsi:** Mengubah action form secara dinamis berdasarkan format yang dipilih

---

### 3. Data Flow

```
User di Data.php
    ↓
Isi form & Klik "Cari"
    ↓
Server proses & tampilkan data
    ↓
User pilih format "Excel" di dropdown
    ↓
JavaScript updateExportAction() ubah form.action → "export_excel.php"
    ↓
User klik "Ekspor"
    ↓
Form POST ke export_excel.php dengan:
  - data (serialized)
  - variables (comma-separated)
  - time_type (hourly/daily/monthly)
  - start_date
  - end_date
    ↓
export_excel.php:
  - Deserialize data
  - Create spreadsheet
  - Create sheets per variabel
  - Isi data & apply formatting
  - Generate file & download
    ↓
User terima file .xlsx
```

---

### 4. Struktur Data yang Diterima

#### Dari Data.php - Hourly:
```php
$data = [
    [
        'tanggal' => '2023-01-01',
        'temp_0' => 25.3, 'temp_1' => 24.8, ... 'temp_23' => 22.1,
        'humid_0' => 75.2, 'humid_1' => 77.5, ... 'humid_23' => 82.3,
        'wind_speed_0' => 2.5, 'wind_speed_1' => 2.3, ... 'wind_speed_23' => 1.8,
        'wind_dir_0' => '45', 'wind_dir_1' => '50', ... 'wind_dir_23' => '35',
    ],
    ...
]
```

#### Dari Data.php - Daily/Monthly:
```php
$data = [
    [
        'tanggal' => '2023-01-01', // atau '2023-01-01' untuk monthly
        'temp_max' => 32.5, 'temp_min' => 20.3, 'temp_avg' => 26.4,
        'humid_max' => 95.0, 'humid_min' => 65.0, 'humid_avg' => 80.5,
        'wind_speed_max' => 8.5, 'wind_speed_avg' => 4.2,
        'wind_dir_max' => '180', 'wind_dir_avg' => '175',
        'rainfall' => 12.5,
    ],
    ...
]
```

---

### 5. Output Format

#### Filename:
`export-YYYY-MM-DD-YYYY-MM-DD-YYYY-MM-DD-HH-MM-SS.xlsx`

Contoh: `export-2023-01-01-2023-01-31-2026-01-26-14-30-45.xlsx`

#### Sheet Names:
- `Curah Hujan` (jika ada rainfall)
- `Suhu` (jika ada temperature)
- `Kelembapan` (jika ada humidity)
- `Angin_Arah` (jika ada wind, untuk arah)
- `Angin_Kecepatan` (jika ada wind, untuk kecepatan)

#### Content Type:
`application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`

---

### 6. Styling Details

#### Header Cell:
- Font: Bold, White, Center
- Fill: Solid Green (#32A852)
- Border: 1px Black
- Row Height: 25pt

#### Date Column Cell:
- Font: Bold, Black, Center
- Fill: Solid Gray (#F0F0F0)
- Border: 1px Black

#### Data Cell:
- Font: Regular, Black, Center
- Fill: White
- Border: 1px Black

#### Column Width:
- Date Column: 15 units
- Hour/Data Columns: 12-18 units (tergantung content)

#### Freeze Panes:
- Freeze Point: B2
- Effect: Header row (row 1) & Date column (column A) tetap visible saat scroll

---

### 7. Error Handling

- Validasi HTTP method (harus POST)
- Validasi kehadiran POST parameters
- Validasi unserialize() untuk data
- Validasi variabel array dengan whitelist
- Die dengan pesan error jika invalid

---

### 8. Dependencies

- **PHPSpreadsheet** v2.1 (dari composer)
- PHP >= 7.0 (untuk use statement dan modern syntax)

---

### 9. Performance Notes

- Tidak ada pagination (semua data load ke memory)
- Cocok untuk dataset hingga 10,000+ rows
- Untuk dataset sangat besar (100,000+ rows), pertimbangkan:
  - Chunked export
  - Streaming output
  - Database cursor/pagination

---

## Testing Notes

### Hourly Data Test:
1. Select: Temperature, start_date: 2023-01-01, end_date: 2023-01-10, Type: Hourly
2. Verify Excel output:
   - 10 rows data (1 per tanggal)
   - 25 columns (Tanggal + 24 jam)
   - Header hijau
   - Date column abu-abu
   - Freeze pane at B2

### Daily Data Test:
1. Select: Temperature + Humidity, start_date: 2023-01-01, end_date: 2023-01-31, Type: Daily
2. Verify Excel output:
   - 31 rows data
   - 2 sheets: "Suhu" & "Kelembapan"
   - Suhu sheet: Kolom B-D (Max/Min/Avg)
   - Kelembapan sheet: Kolom B-D (Max/Min/Avg)

### Wind Data Test:
1. Select: Wind, Type: Hourly
2. Verify Excel output:
   - 2 sheets: "Angin_Arah" & "Angin_Kecepatan"
   - Arah Angin: Nilai dikonversi ke compass (N, NE, E, dll)
   - Kecepatan Angin: Nilai numerik

