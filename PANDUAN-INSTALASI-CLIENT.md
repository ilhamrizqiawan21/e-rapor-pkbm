# Panduan Instalasi E-Rapor PKBM

Panduan ini dipakai untuk memilih salah satu cara menjalankan E-Rapor PKBM:

1. **Localhost dengan Laragon** — aplikasi berjalan di satu komputer PKBM.
2. **Online di VPS/server** — aplikasi dapat dibuka oleh Admin, Guru, dan Kepala PKBM dari jaringan internet.

Gunakan **satu pilihan saja** untuk satu instalasi. Kode aplikasi sama; yang berbeda hanya file `.env` dan servernya.

## Pilih cara yang sesuai

| Kebutuhan | Pilihan |
|---|---|
| Dipakai di satu komputer atau jaringan lokal | Laragon |
| Dipakai dari rumah, beberapa lokasi, atau banyak perangkat | VPS/server |
| Tidak ingin membayar server bulanan | Laragon |
| Ingin bisa dibuka kapan saja dari internet | VPS/server |

## Sebelum mulai

Siapkan:

- Folder source code E-Rapor PKBM.
- PHP **8.3 atau lebih baru**.
- Composer.
- Google Chrome atau Chromium untuk membuat PDF rapor.
- Node.js dan npm hanya diperlukan saat pertama kali membangun aset tampilan.
- Backup database lama jika ini bukan instalasi baru.

> Jangan menjalankan `php artisan migrate:fresh` pada instalasi yang sudah berisi data. Perintah itu menghapus seluruh tabel.

---

## Pilihan A — Localhost dengan Laragon

### 1. Pasang Laragon

Pasang Laragon pada Windows, lalu pastikan PHP yang aktif adalah versi 8.3 atau lebih baru.

Buka **Terminal Laragon** dan cek:

```bash
php -v
composer --version
node -v
npm -v
```

Jika `php -v` masih di bawah 8.3, pilih PHP 8.3 melalui menu Laragon sebelum melanjutkan.

### 2. Salin source code

Letakkan folder aplikasi di:

```text
C:\laragon\www\e-rapor-pkbm
```

Nama folder boleh berbeda, tetapi nama tersebut membuat alamat Laragon menjadi:

```text
http://e-rapor-pkbm.test
```

### 3. Pilih konfigurasi Laragon

Di Terminal Laragon, masuk ke folder aplikasi lalu jalankan:

```powershell
cd C:\laragon\www\e-rapor-pkbm
Copy-Item .env.laragon.example .env
New-Item database\database.sqlite -ItemType File -Force
```

Buka file `.env`, lalu pastikan `APP_URL` sama dengan alamat yang akan dibuka. Contoh:

```dotenv
APP_URL=http://e-rapor-pkbm.test
```

Jika Chrome terpasang di lokasi berbeda, ubah juga:

```dotenv
PDF_BROWSER_BINARY="C:/Program Files/Google/Chrome/Application/chrome.exe"
```

### 4. Pasang aplikasi

Jalankan satu per satu:

```bash
composer install
php artisan key:generate
php artisan migrate --seed --force
npm install
npm run build
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
```

`migrate --seed` pada instalasi pertama membuat struktur awal, role, akun demo, dan data contoh.

Pada Laragon, `ERAPOR_SEED_DEMO_DATA=true` sengaja mengisi data contoh agar aplikasi langsung dapat dicoba. Pada VPS, profil production memakai `ERAPOR_SEED_DEMO_DATA=false`, sehingga yang dibuat hanya institusi awal, role, akun awal, dan master tingkat. Data siswa, kelas, nilai, dan rapor dimasukkan oleh client sendiri.

### 5. Jalankan

1. Buka Laragon.
2. Klik **Start All**.
3. Buka `http://e-rapor-pkbm.test`.

Jika virtual host belum muncul, klik **Reload** pada Laragon. Alternatif sementara:

```bash
php artisan serve
```

Kemudian buka `http://127.0.0.1:8000` dan sesuaikan `APP_URL` di `.env`.

### 6. Akun awal

| Peran | Username | Password awal |
|---|---|---|
| Admin | `admin` | `password` |
| Guru | `guru` | `password` |
| Kepala PKBM | `kepsek` | `password` |

Segera masuk sebagai Admin dan ubah password seluruh akun demo. Akun demo hanya untuk instalasi awal.

### 7. Backup localhost

Backup manual dapat dilakukan dari menu **Pengaturan PKBM → Cadangan data**. Dari terminal, perintahnya:

```bash
php artisan backup:database
```

File backup tersimpan di `storage/app/backups/database` dan tidak boleh dihapus sebelum dipindahkan ke media cadangan lain.

---

## Pilihan B — VPS/server online

Bagian ini menggunakan contoh Ubuntu, Nginx, MySQL, dan PHP-FPM. Jika server memakai cPanel, gunakan langkah yang sama dengan menu cPanel; **Document Root harus diarahkan ke folder `public`**.

### 1. Spesifikasi minimum yang disarankan

- Ubuntu 22.04/24.04 atau server Linux yang masih mendapat pembaruan keamanan.
- 2 vCPU dan 4 GB RAM untuk penggunaan awal.
- PHP 8.3 atau lebih baru dengan ekstensi Laravel umum: `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`, `xml`, `ctype`, `json`, dan `bcmath`.
- MySQL/MariaDB.
- Nginx atau Apache.
- Google Chrome/Chromium headless untuk PDF.
- Composer dan Node.js/npm untuk instalasi/build.
- Domain dan HTTPS.

### 2. Buat database

Contoh melalui MySQL:

```sql
CREATE DATABASE erapor_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'erapor_user'@'localhost' IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON erapor_production.* TO 'erapor_user'@'localhost';
FLUSH PRIVILEGES;
```

Gunakan password database yang berbeda dari password akun aplikasi.

### 3. Upload aplikasi

Contoh lokasi aplikasi:

```text
/var/www/e-rapor-pkbm
```

Masuk ke folder tersebut, lalu siapkan konfigurasi online:

```bash
cd /var/www/e-rapor-pkbm
cp .env.production.example .env
nano .env
```

Minimal, ubah nilai berikut:

```dotenv
APP_URL=https://erapor.contoh.sch.id
APP_KEY=

DB_DATABASE=erapor_production
DB_USERNAME=erapor_user
DB_PASSWORD=password-database-kuat

MAIL_HOST=smtp.contoh.sch.id
MAIL_USERNAME=akun-email
MAIL_PASSWORD=password-email
MAIL_FROM_ADDRESS=admin@contoh.sch.id
```

`APP_DEBUG` harus `false`, `SECURITY_FORCE_HTTPS` harus `true`, dan `SECURITY_HSTS` sebaiknya `true` setelah HTTPS sudah dipastikan berjalan.

### 4. Pasang aplikasi di server

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --seed --force
npm ci
npm run build
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Untuk instalasi yang sudah memiliki data, gunakan `php artisan migrate --force` tanpa `--seed` kecuali memang ingin memperbarui data seed.

Pada VPS production, file `.env.production.example` sudah memakai `ERAPOR_SEED_DEMO_DATA=false`. Setelah login, ubah nama dan password akun awal sebelum mulai mengisi data PKBM.

### 5. Arahkan web server ke folder `public`

Contoh inti konfigurasi Nginx:

```nginx
server {
    server_name erapor.contoh.sch.id;
    root /var/www/e-rapor-pkbm/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Setelah itu pasang HTTPS menggunakan Certbot atau fasilitas SSL dari provider server. Jangan mengarahkan domain ke `/var/www/e-rapor-pkbm`; arahkan ke `/var/www/e-rapor-pkbm/public`.

### 6. Scheduler dan backup otomatis

Tambahkan satu cron pada server:

```cron
* * * * * www-data cd /var/www/e-rapor-pkbm && php artisan schedule:run >> /dev/null 2>&1
```

Jadwal backup default adalah setiap hari pukul `02:30` waktu Asia/Jakarta. Pastikan folder storage dapat ditulis oleh `www-data` dan backup disalin lagi ke server/media cadangan lain.

Jika nanti aplikasi memakai pekerjaan antrean, jalankan worker menggunakan Supervisor atau systemd:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120
```

### 7. Pengujian setelah online

Buka alamat domain dan periksa:

1. Halaman login dapat dibuka melalui HTTPS.
2. Login Admin, Guru, dan Kepala PKBM berhasil.
3. Logo dan favicon tampil.
4. Input nilai tersimpan otomatis.
5. Preview PDF identitas, nilai, dan rapor lengkap berhasil.
6. Backup manual berhasil dibuat.
7. File di luar folder `public` tidak dapat dibuka langsung dari browser.

---

## Memperbarui aplikasi yang sudah berjalan

Sebelum update, buat backup dari menu **Cadangan data** atau jalankan:

```bash
php artisan backup:database
```

Lalu jalankan:

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

Jangan mengganti file `.env`, jangan menjalankan `migrate:fresh`, dan jangan menghapus folder `storage` saat update.

## Masalah umum

| Gejala | Penyebab yang biasanya terjadi | Perbaikan |
|---|---|---|
| `No application encryption key` | `APP_KEY` belum dibuat | Jalankan `php artisan key:generate`, lalu cache konfigurasi ulang |
| Halaman 404 di Laragon | Virtual host belum dimuat | Klik **Reload** Laragon atau gunakan `php artisan serve` |
| CSS/tampilan tidak berubah | Asset belum dibangun | Jalankan `npm run build` |
| PDF gagal dibuat | Chrome/Chromium tidak ditemukan | Isi `PDF_BROWSER_BINARY` dengan lokasi executable yang benar |
| `500` setelah mengubah `.env` | Cache konfigurasi masih memakai nilai lama | Jalankan `php artisan optimize:clear` lalu `php artisan config:cache` |
| Login berputar kembali ke login | `APP_URL`, domain cookie, atau HTTPS tidak cocok | Samakan `APP_URL` dengan alamat browser dan periksa `SESSION_SECURE_COOKIE` |
| Upload/logo gagal | Permission storage tidak benar | Pastikan `storage` dan `bootstrap/cache` dapat ditulis oleh web server |
| Backup otomatis tidak muncul | Cron belum dipasang atau tidak berjalan | Periksa cron dan jalankan `php artisan schedule:run` secara manual |

## Keamanan minimum sebelum diserahkan ke client

- Ganti semua password demo.
- Pastikan `APP_DEBUG=false` pada VPS.
- Gunakan HTTPS pada VPS.
- Jangan membagikan `.env`, `APP_KEY`, password database, atau file backup melalui chat umum.
- Jadwalkan backup dan uji restore secara berkala.
- Pastikan web server hanya membuka folder `public`.
