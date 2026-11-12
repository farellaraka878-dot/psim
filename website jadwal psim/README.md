# JADWAL PSIM JOGJA

Website PHP dan MySQL sederhana untuk jadwal pertandingan PSIM, komentar suporter, rating, dan dashboard admin. Tidak menggunakan Node.js atau framework.

## Deploy ke Vercel

Proyek ini menggunakan Docker dengan FrankenPHP karena PHP bukan runtime resmi Vercel. Pastikan `Dockerfile` dan `Caddyfile` tetap berada di folder utama repository.

1. Siapkan database MySQL/MariaDB di layanan eksternal yang bisa diakses dari internet. Vercel tidak menyediakan server MySQL untuk aplikasi ini.
2. Impor `htdocs/database.sql` ke database tersebut.
3. Hubungkan repository ke Vercel dan deploy. Vercel akan mendeteksi `Dockerfile` di folder utama. Jika diminta memilih framework, pilih **Container**.
4. Di **Project Settings → Environment Variables**, tambahkan:

   | Nama | Nilai |
   | --- | --- |
   | `PSIM_DB_HOST` | Host database |
   | `PSIM_DB_PORT` | Port database (opsional, default `3306`) |
   | `PSIM_DB_NAME` | Nama database |
   | `PSIM_DB_USER` | Username database |
   | `PSIM_DB_PASS` | Password database |
   | `PSIM_APP_KEY` | Kunci acak rahasia minimal 32 karakter |

   Buat kunci dengan PHP: `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Jangan gunakan kunci contoh atau masukkan rahasia ke repository.
5. Pastikan database mengizinkan koneksi dari deployment Vercel dan gunakan koneksi TLS bila diwajibkan oleh penyedia database.
6. Simpan variabel untuk environment **Production** (dan **Preview** bila diperlukan), lalu deploy ulang.
7. Setelah situs aktif, buka `/admin/setup.php` untuk membuat akun admin. Endpoint tersebut terkunci otomatis setelah akun pertama dibuat.

File `htdocs/database.sql`, `.htaccess`, konfigurasi, dan helper PHP diblokir dari akses HTTP oleh `Caddyfile`. File statis seperti CSS dan JavaScript tetap dilayani dari `htdocs/assets/`.

PHP menyimpan session secara lokal secara default. Penyimpanan itu tidak dibagi antar-instance Vercel; jika deployment berjalan pada beberapa instance, sesi login/form bisa tidak konsisten. Untuk penggunaan multi-instance, konfigurasikan session handler bersama seperti Redis.

## Instalasi di InfinityFree

1. Buat database MySQL dari panel InfinityFree dan impor `htdocs/database.sql` melalui phpMyAdmin.
2. Edit `htdocs/config/database.php` dengan host, nama database, username, dan password dari panel hosting.
3. Ganti `APP_KEY` dengan string acak rahasia yang panjang (setidaknya 32 karakter). Jangan gunakan contoh bawaan.
4. Unggah **isi folder proyek `htdocs/`** ke folder `htdocs` (atau folder dokumen web yang disediakan akun) di InfinityFree.
5. Buka `/admin/setup.php` dan buat username serta password admin. Password harus minimal 12 karakter. Setelah admin dibuat, endpoint setup akan terkunci otomatis.
6. Masuk dari tautan **ADMIN** pada website.

## Menjalankan secara lokal

Jika PHP tersedia, jalankan server bawaan PHP dari folder utama proyek:

```powershell
$env:PSIM_DB_HOST = '127.0.0.1'
$env:PSIM_DB_NAME = 'nama_database_lokal'
$env:PSIM_DB_USER = 'root'
$env:PSIM_DB_PASS = ''
$env:PSIM_APP_KEY = 'isi-dengan-kunci-acak-minimal-32-karakter'
php -S 127.0.0.1:8001 -t .\htdocs
```

Pastikan MySQL aktif dan skema `htdocs/database.sql` sudah diimpor ke database tersebut.

Gunakan PHP 8.1 atau yang lebih baru, MySQL/MariaDB dengan PDO MySQL, dan ekstensi `mbstring`. Aktifkan HTTPS di panel hosting agar session dan cookie rating memakai koneksi aman.

## Jadwal pertandingan

Database awal memuat laga Persik Kediri–PSIM pada 21 November 2026 dan daftar pertandingan liga serta I.League Cup yang dimasukkan untuk musim 2026/27. Jadwal dan tautan rujukan yang telah ditambahkan tersimpan di database; admin tetap perlu memeriksa sumber resmi secara berkala karena jadwal, waktu, dan stadion dapat berubah. Laga Persik–PSIM awalnya masih bertanda TBC, kemudian waktunya diperbarui menjadi 15.30 WIB sesuai jadwal resmi I.League.

Jika database situs sudah dibuat sebelum perubahan struktur jadwal, tambahkan kolom kompetisi dan kandang/tandang dengan perintah SQL berikut sebelum menjalankan versi kode terbaru:

```sql
ALTER TABLE matches
    ADD COLUMN psim_home TINYINT(1) NOT NULL DEFAULT 1 AFTER opponent_logo,
    ADD COLUMN competition VARCHAR(120) NOT NULL DEFAULT 'BRI Super League 2026/27' AFTER psim_home;
```

Logo PSIM dimuat dari situs resmi PSIM, sementara logo Persik berasal dari aset I.League. Koneksi internet diperlukan untuk menampilkan gambar dari sumber eksternal.

## Keamanan dan data

- Komentar, rating, akun admin, jadwal, dan penghitung kunjungan disimpan di MySQL.
- Penghitung kunjungan menghitung satu kali per sesi browser, bukan setiap pemuatan halaman; dashboard menampilkan total dan ringkasan harian 7 hari terakhir.
- Rating dibatasi satu per browser dan maksimal sekali per alamat jaringan dalam 24 jam; komentar memiliki honeypot, pembatasan kirim, dan pencegahan duplikat.
- Dashboard admin memakai session, CSRF token, prepared statements, dan password hash PHP.
- Simpan kredensial database dan `APP_KEY` secara privat, buat backup database berkala, dan hapus akses setup setelah akun admin dibuat.
