# KosKu — Manajemen Kos-Kosan

Aplikasi manajemen kos-kosan berbasis PHP dan SQLite untuk keperluan pembelajaran pengujian perangkat lunak.

## Fitur
- Daftar kamar beserta status hunian
- Manajemen kamar (CRUD) oleh admin
- Pencatatan hunian (masuk/keluar penyewa)
- Manajemen pembayaran bulanan

## Cara Menjalankan

### Prasyarat
- PHP 8.x
- Ekstensi PHP: `pdo_sqlite`

### Langkah Instalasi

1. **Clone atau unduh** folder `php-manajemen-kos`.
2. **Masuk ke folder project:**
   ```bash
   cd php-manajemen-kos
   ```
3. **Jalankan server PHP bawaan:**
   ```bash
   php -S localhost:8001
   ```
4. **Buka browser** dan akses `http://localhost:8001`

Database SQLite dibuat otomatis saat pertama kali diakses.

## Akun Demo

| Role  | Email              | Password  |
|-------|--------------------|-----------|
| Admin | admin@kosku.com    | admin123  |
| Penyewa | andi@mail.com    | user123   |

## Catatan untuk Mahasiswa

Aplikasi ini adalah **versi latihan** yang mengandung bug untuk praktikum pengujian perangkat lunak.
