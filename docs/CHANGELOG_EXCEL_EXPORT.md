# CHANGELOG - Fitur Export Excel (.xlsx)

## Tanggal: 26 Januari 2026

### Fitur Baru: Export Data ke Format Excel (.xlsx)

#### Deskripsi
Telah ditambahkan fitur export data ke format Excel (.xlsx) dengan menggunakan library **PHPSpreadsheet**. Fitur ini tersedia untuk data hourly dan daily, dengan support untuk format monthly hanya PDF.

#### File yang Ditambahkan
1. **export_excel.php** - File utama untuk generate dan download file Excel

#### File yang Dimodifikasi
1. **Data.php** - Penambahan opsi "Excel" di dropdown export format, dan JavaScript untuk handle routing ke file export_excel.php

#### Spesifikasi Implementasi

##### Struktur File Excel:
- **1 file Excel** per export dengan multiple sheets
- **1 sheet per variabel data:**
  - `Curah Hujan` - Data curah hujan
  - `Suhu` - Data suhu (temperature)
  - `Kelembapan` - Data kelembapan (humidity)
  - `Angin_Arah` - Data arah angin
  - `Angin_Kecepatan` - Data kecepatan angin

##### Struktur Tabel:

###### Untuk Data Hourly:
- **Kolom A:** Tanggal (format YYYY-MM-DD)
- **Kolom B-Y:** Jam 00:00 sampai 23:00 (24 jam)
- **1 baris = 1 tanggal** (tidak terpecah)

###### Untuk Data Daily/Monthly:
- **Kolom A:** Tanggal
- **Kolom B-D (Suhu/Kelembapan):** Max, Min, Rata-Rata
- **Kolom B-C (Angin):** Max, Rata-Rata
- **Kolom B (Curah Hujan):** Total

##### Formatting:

###### Header:
- ✅ **Bold** - Teks tebal
- ✅ **Background Hijau** (#32A852) - Warna latar hijau
- ✅ **Text Center** - Teks rata tengah

###### Kolom Tanggal:
- ✅ **Bold** - Teks tebal
- ✅ **Background Abu-abu** (#F0F0F0) - Warna latar abu-abu
- ✅ **Text Center** - Teks rata tengah

##### Fitur Lainnya:
- ✅ **Freeze Panes** - Header (row 1) dan kolom tanggal (column A) terbeku
- ✅ **Auto-adjust Column Width:**
  - Kolom tanggal: 15 unit
  - Kolom jam/nilai: 12-18 unit (tergantung jenis data)
- ✅ **Data Kosong** - Ditampilkan sebagai "-" (dash)
- ✅ **Konsistensi Data** - Order dan format data sama dengan versi PDF
  - Wind direction dikonversi ke 8-point compass (N, NE, E, SE, S, SW, W, NW)
  - Nilai numerik dibulatkan ke 1 desimal
  - Format tanggal monthly: "Nama Bulan Tahun" (e.g., "Januari 2023")

#### Contoh Penggunaan:

1. Buka halaman **Data.php**
2. Isi form pencarian data:
   - Tanggal Mulai
   - Tanggal Selesai
   - Pilih Variabel Data (bisa lebih dari satu)
   - Pilih Tipe Data (Jam/Hari)
3. Klik tombol "Cari"
4. Di bagian bawah, di dropdown "Ekspor sebagai", pilih **"Excel"**
5. Klik tombol "Ekspor"
6. File .xlsx akan otomatis diunduh

#### Dukungan Format:
- **Hourly Data:** PDF, Word, Excel ✅
- **Daily Data:** PDF, Word, Excel ✅
- **Monthly Data:** PDF only (Excel tidak tersedia)

#### Teknologi:
- **Library:** PHPSpreadsheet v2.1 (already included in composer.json)
- **Format Output:** .xlsx (ECMA-376)
- **Encoding:** UTF-8

#### Notes:
- Rainfall data hanya tersedia untuk daily dan monthly (tidak ada hourly)
- Wind data dibagi menjadi 2 sheets: Arah Angin dan Kecepatan Angin
- Format nama file: `export-YYYY-MM-DD-YYYY-MM-DD-YYYY-MM-DD-HH-MM-SS.xlsx`

---

### Testing Checklist:
- [ ] Export hourly temperature ke Excel
- [ ] Export hourly humidity ke Excel
- [ ] Export hourly wind (arah + kecepatan) ke Excel
- [ ] Export daily data (semua variabel) ke Excel
- [ ] Verify header formatting (bold, hijau, center)
- [ ] Verify date column formatting (bold, abu-abu, center)
- [ ] Verify freeze panes berfungsi
- [ ] Verify column width auto-adjust
- [ ] Verify data kosong ditampilkan sebagai "-"
- [ ] Verify wind direction converter (degrees to compass)
- [ ] Verify file download dengan nama yang benar

