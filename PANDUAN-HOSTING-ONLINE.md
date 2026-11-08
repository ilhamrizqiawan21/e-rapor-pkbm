# Panduan Instalasi di Hosting Online

Panduan ini untuk VPS, server Apache/Nginx, atau shared hosting yang mendukung PHP. Jalankan aplikasi dengan HTTPS dan arahkan web server ke folder `public` jika provider mengizinkannya.

## Persiapan server

Disarankan: PHP 8.3 atau lebih baru, ekstensi `mbstring`, `openssl`, `pdo_mysql` atau `pdo_pgsql`, `fileinfo`, `xml`, `ctype`, dan `bcmath`, MySQL/MariaDB atau PostgreSQL, Composer, Node.js/npm saat build aset, Chrome/Chromium headless untuk PDF, serta domain/subdomain dengan HTTPS.

## 1. Buat database

Contoh MySQL:

```sql
CREATE DATABASE erapor_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'erapor_user'@'localhost' IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON erapor_production.* TO 'erapor_user'@'localhost';
FLUSH PRIVILEGES;
```

Gunakan password database yang berbeda dari password pengguna aplikasi.

## 2. Upload dan konfigurasi aplikasi

Contoh lokasi:

```text
/var/www/e-rapor-pkbm
```

Salin konfigurasi production dan edit nilainya:

```bash
cd /var/www/e-rapor-pkbm
cp .env.production.example .env
nano .env
```

Nilai penting:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erapor.contoh.sch.id
APP_KEY=base64:ISI_DENGAN_KEY_UNIK

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erapor_production
DB_USERNAME=erapor_user
DB_PASSWORD=password-database-kuat

SECURITY_FORCE_HTTPS=true
SECURITY_HSTS=true
```

Jika `APP_KEY` masih kosong pada instalasi baru, jalankan `php artisan key:generate --force` satu kali. Jangan menjalankan perintah itu pada production yang sudah memiliki data karena data terenkripsi lama tidak dapat dibuka dengan key baru.

## 3. Pasang dependency dan migrasi

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
```

Pada instalasi baru yang membutuhkan akun dan master awal, gunakan `php artisan migrate --seed --force`. Pada instalasi yang sudah berisi data, cukup gunakan `migrate --force`.

Jika Node.js tersedia di server:

```bash
npm ci
npm run build
```

Jika Node.js tidak tersedia, paket deployment sudah menyertakan `public/build` hasil build. Jangan menghapus folder tersebut.

Lanjutkan dengan:

```bash
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4. Arahkan web server ke `public`

### Nginx

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

Sesuaikan nama domain, path aplikasi, dan versi socket PHP-FPM dengan server sebenarnya.

### Apache dengan document root `public`

Atur `DocumentRoot` ke `/var/www/e-rapor-pkbm/public`, aktifkan `mod_rewrite`, izinkan `.htaccess`, lalu reload Apache. File `public/.htaccess` akan meneruskan route ke `public/index.php`.

### Apache dengan subfolder

Jika aplikasi harus dibuka melalui `https://contoh.sch.id/e-rapor-pkbm`, gunakan template `deploy/apache/e-rapor-pkbm-alias.conf`. Ganti path aplikasi dan socket PHP-FPM. Pastikan ada:

```apache
SetEnv ERAPOR_SUBPATH /e-rapor-pkbm
```

Dan set:

```dotenv
APP_URL=https://contoh.sch.id/e-rapor-pkbm
```

Jika aplikasi dibuka dari domain/subdomain root, jangan mengatur subpath dan gunakan `APP_URL` tanpa tambahan path. Dengan pola ini, satu source code mendukung root maupun subfolder.

## 5. Hak akses dan scheduler

Berikan hak tulis hanya pada folder yang diperlukan:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

Tambahkan scheduler satu kali saja:

```cron
* * * * * www-data cd /var/www/e-rapor-pkbm && php artisan schedule:run >> /dev/null 2>&1
```

Jika queue database digunakan, jalankan worker dengan Supervisor/systemd:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120
```

## 6. Pemeriksaan setelah online

1. Login dan halaman utama dapat dibuka melalui HTTPS.
2. CSS, JavaScript, favicon, dan logo tampil.
3. Login Admin, Guru, dan Kepala PKBM berhasil.
4. Input nilai, export Excel, dan PDF berhasil.
5. Backup manual berhasil dibuat.
6. File `.env`, `.git`, `storage/app`, dan `vendor` tidak dapat diakses langsung dari browser.
7. `APP_DEBUG=false`, `APP_KEY` unik, dan credential tidak berada di Git.

## Pemeliharaan dan update

Sebelum update, backup database dan file penting. Setelah mengambil perubahan baru:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jangan mengganti `.env` production dengan file contoh. File contoh hanya template; credential production harus tetap berada di server atau secret manager.
