# Travel Management System

Ringkasan singkat dan instruksi menjalankan aplikasi lokal.

## Deskripsi
Aplikasi PHP sederhana (MVC ringan) untuk manajemen travel: pesanan, dokumen (quotation/invoice/receipt), klien, lampiran, modul akuntansi dasar, laporan dan analysis.

## Prasyarat
- PHP 7.4+ (direkomendasikan PHP 8.x)
- Ekstensi: `pdo_mysql`, `mbstring`, `json`, `ctype`, `fileinfo`, `openssl`, `curl`, `gd` (opsional untuk image)
- MySQL/MariaDB
- Webserver (Apache/Nginx) atau PHP built-in server

## Struktur penting
- `index.php` — entrypoint utama dan router sederhana
- `config/` — konfigurasi (constants, database, routes, session)
- `controllers/` — controller aplikasi
- `models/` — model (ada `Model.php` sebagai base)
- `views/` — tampilan/template
- `uploads/`, `cache/`, `logs/` — direktori runtime yang harus bisa ditulis
- `sql/install.sql` — skema DB awal

## Konfigurasi
1. Salin/ubah `config/database.env.php` (direkomendasikan) atau edit `config/database.php` langsung.

Contoh `config/database.env.php`:

```php
<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'travel_db');
define('DB_USER', 'dbuser');
define('DB_PASS', 'dbpass');
```

2. Pastikan `config/constants.php` berisi `BASE_URL` yang benar jika diperlukan.
3. Pastikan direktori berikut dapat ditulis oleh proses PHP: `uploads/`, `cache/`, `logs/` dan subfoldernya.

## Menjalankan secara lokal (cepat, tanpa webserver lain)
Saya sediakan `router.php` kecil untuk PHP built-in server. Jalankan dari root proyek:

```bash
# di PowerShell atau CMD (Windows)
php -S localhost:8000 router.php

# di Linux/macOS
php -S 0.0.0.0:8000 router.php
```

Buka: `http://localhost:8000/` atau `http://localhost:8000/install` untuk menjalankan wizard instalasi.

## Alternatif: Menggunakan Apache / Nginx
- Set `DocumentRoot` ke folder proyek.
- Pastikan semua request diarahkan ke `index.php` (rewrite rules). Jika menggunakan Apache, aktifkan `mod_rewrite` dan buat `.htaccess` yang merutekan ke `index.php?_url={REQUEST_URI}` atau gunakan konfigurasi server yang setara.

## Instalasi awal via wizard
1. Akses `/install` di browser.
2. Ikuti langkah pengecekan requirement, konfigurasi database, data perusahaan, akun admin, dan finish.
3. Wizard akan membuat file `config/installed.php` jika sukses.

## Jika ingin import manual
1. Buat database kosong dan import `sql/install.sql` jika mau.
2. Buat `config/database.env.php` seperti contoh.

## Troubleshooting
- Pesan error kosong: aktifkan debug sementara di `config/constants.php` (set `APP_DEBUG` ke `true`).
- Koneksi DB gagal: cek `config/database.env.php` dan pastikan MySQL menerima koneksi dari host.
- Permission error: beri write permissions pada `uploads/`, `cache/`, `logs/`.

## Tips keamanan
- Jangan commit `config/database.env.php` berisi kredensial. Tambahkan ke `.gitignore`.
- Aktifkan HTTPS pada server publik.
- Non-aktifkan `display_errors` di produksi (`APP_DEBUG = false`).

## Perlu bantuan lebih lanjut?
Saya bisa:
- Buat `.htaccess` untuk Apache atau contoh konfigurasi Nginx.
- Tambahkan script `docker-compose` untuk lingkungan dev.
- Menyusun checklist hardening.

---
README dibuat otomatis oleh tool. File tambahan: `router.php` (helper untuk PHP built-in server).