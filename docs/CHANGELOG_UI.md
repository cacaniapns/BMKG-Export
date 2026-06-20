# Changelog UI - Data.php

## Perubahan yang Dilakukan (Tanggal: 7 Januari 2026)

### 1. **Pilihan Variabel Data - Dari Dropdown Menjadi Checkbox**
   - **Sebelumnya**: Pengguna hanya bisa memilih 1 variabel data (curah hujan, temperatur, kelembapan, atau angin)
   - **Sekarang**: Pengguna bisa memilih **multiple variabel** sekaligus menggunakan checkbox
   - **Keuntungan**: Fleksibel - bisa pilih 1, 2, 3, atau semua variabel sesuai kebutuhan

### 2. **Tipe Data - Dari Dropdown Menjadi Radio Button**
   - **Baru ditambahkan**: Opsi untuk memilih tipe data waktu
   - **Pilihan**:
     - **Jam** (hourly) - Data per jam
     - **Hari** (daily) - Data per hari
     - **Bulan** (monthly) - Data per bulan
   - **Catatan**: Radio button memastikan hanya satu pilihan yang bisa dipilih

### 3. **Tampilan Hasil Data**
   - Jika memilih multiple variabel, setiap variabel akan ditampilkan dalam tabel terpisah
   - Setiap tabel memiliki heading yang jelas (Data Temperatur, Data Kelembapan, dll)
   - Struktur data lebih terorganisir dan mudah dibaca

### 4. **Styling Improvements**
   - Checkbox dan radio button diberi styling yang konsisten dengan tema aplikasi
   - Fieldset dengan border dan background untuk grouping yang lebih jelas
   - Hover effect pada checkbox dan radio button untuk UX yang lebih baik
   - Warna accent menggunakan tema utama aplikasi (teal #009879)

## Struktur Form Baru

```
┌─ Tanggal Mulai (date input)
├─ Tanggal Selesai (date input)
├─ Pilih Variabel Data (checkbox group)
│  ├─ □ Curah Hujan
│  ├─ □ Temperatur
│  ├─ □ Kelembapan
│  └─ □ Angin
├─ Tipe Data (radio group)
│  ├─ ○ Jam
│  ├─ ○ Hari
│  └─ ○ Bulan
└─ Button Cari
```

## Contoh Penggunaan

1. **Membandingkan 2 variabel dalam periode tertentu**:
   - Pilih Tanggal Mulai dan Selesai
   - Check "Temperatur" dan "Kelembapan"
   - Pilih "Hari"
   - Klik "Cari"
   - Hasil: 2 tabel terpisah (Data Temperatur & Data Kelembapan)

2. **Melihat semua variabel dengan detail per bulan**:
   - Pilih range tanggal
   - Check semua variabel
   - Pilih "Bulan"
   - Hasil: 4 tabel (Temperatur, Kelembapan, Curah Hujan, Angin)

## Backend Changes

- Fungsi `getDataByDateRangeAndType()` diganti menjadi `getDataByVariablesAndTime()`
- Validasi lebih ketat untuk multiple selections
- Data disimpan dalam array berdasarkan tipe variabel
- Export function tetap kompatibel dengan multiple data types

## File yang Diubah

1. **Data.php** - Logika form dan tampilan data
2. **style.css** - Styling untuk checkbox, radio button, dan layout baru
