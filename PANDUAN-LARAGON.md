# Panduan Instalasi di Laragon

Panduan ini untuk menjalankan E-Rapor PKBM di komputer Windows menggunakan Laragon. Kode aplikasi yang sama juga dapat dipindahkan ke hosting online; yang berbeda hanya file `.env` dan konfigurasi web server.

## Persiapan

Pastikan Laragon memakai PHP 8.3 atau lebih baru, Composer, Node.js/npm, dan Google Chrome atau Chromium untuk membuat PDF.

Buka **Terminal Laragon**, lalu cek:

```powershell
php -v
composer --version
node -v
npm -v
```

## Instalasi baru

1. Ekstrak project ke:

   ```text
   C:\laragon\www\e-rapor-pkbm
   ```

2. Dari Terminal Laragon, masuk ke folder project dan salin konfigurasi:

   ```powershell
   cd C:\laragon\www\e-rapor-pkbm
   Copy-Item .env.laragon.example .env
   New-Item database\database.sqlite -ItemType File -Force
   ```

3. Jika nama folder atau alamat virtual host berbeda, ubah `APP_URL` di `.env`. Contoh default:

   ```dotenv
   APP_URL=http://e-rapor-pkbm.test
   ```

   Untuk virtual host Laragon, gunakan URL root seperti contoh di atas. Subpath hanya diperlukan jika aplikasi dibuka melalui URL seperti `http://localhost/e-rapor-pkbm`; lihat penjelasan di panduan hosting online.

4. Pasang dependency dan siapkan aplikasi:

   ```powershell
   composer install
   php artisan key:generate
   php artisan migrate --seed --force
   npm ci
   npm run build
   php artisan storage:link
   php artisan optimize:clear
   ```

   `migrate --seed` membuat tabel, role, akun demo, dan data contoh. Jangan gunakan `migrate:fresh` pada instalasi yang sudah berisi data karena perintah tersebut menghapus seluruh tabel.

5. Klik **Start All** di Laragon, lalu klik **Reload** bila virtual host belum muncul.

6. Buka:

   ```text
   http://e-rapor-pkbm.test
   ```

   Alternatif tanpa virtual host:

   ```powershell
   php artisan serve
   ```

   Kemudian buka `http://127.0.0.1:8000` dan sesuaikan `APP_URL`.

## Akun demo

| Peran | Username | Password |
|---|---|---|
| Admin | `admin` | `password` |
| Guru | `guru` | `password` |
| Kepala PKBM | `kepsek` | `password` |

Segera ganti semua password demo setelah login. Akun tersebut hanya untuk instalasi awal.

## Chrome dan PDF

Jika PDF gagal dibuat, isi `PDF_BROWSER_BINARY` di `.env` dengan lokasi Chrome. Contoh umum:

```dotenv
PDF_BROWSER_BINARY="C:/Program Files/Google/Chrome/Application/chrome.exe"
```

Gunakan path sebenarnya jika Chrome berada di lokasi lain.

## Backup lokal

Backup dapat dibuat dari menu Admin atau melalui Terminal Laragon:

```powershell
php artisan backup:database
```

Simpan salinan backup di media lain. Jangan mengirim `.env`, `APP_KEY`, atau backup database ke repository.

## Masalah umum

- **Halaman 404:** klik Reload di Laragon dan pastikan `APP_URL` sama dengan alamat browser.
- **CSS/JavaScript tidak tampil:** jalankan `npm ci` lalu `npm run build`.
- **Logo tidak tampil:** jalankan `php artisan storage:link` dan pastikan folder `storage` dapat ditulis.
- **Login berulang:** jalankan `php artisan optimize:clear`, lalu periksa `APP_URL` dan cookie session.
- **PDF gagal:** periksa `PDF_BROWSER_BINARY` dan pastikan Chrome dapat dijalankan oleh PHP.
