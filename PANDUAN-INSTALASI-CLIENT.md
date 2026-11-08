# Panduan Instalasi E-Rapor PKBM

Source code yang sama mendukung dua jalur penggunaan:

1. [Localhost dengan Laragon](PANDUAN-LARAGON.md) untuk penggunaan lokal/LAN.
2. [Hosting online](PANDUAN-HOSTING-ONLINE.md) untuk penggunaan melalui domain dan internet.

Perbedaan kedua jalur hanya berada pada file `.env`, database, dan konfigurasi web server. Jangan menyalin `.env` dari komputer lokal ke server online karena nilai database, cookie, HTTPS, dan mode debug berbeda.

## Aturan keamanan penting

- Jangan meng-commit `.env`, `APP_KEY`, password database, password SMTP, atau backup database.
- Gunakan `APP_DEBUG=true` hanya di localhost; production harus `false`.
- Jangan menjalankan `migrate:fresh` pada aplikasi yang sudah memiliki data.
- Simpan backup di media terpisah dan uji restore pada staging terlebih dahulu.
- Untuk deployment online, arahkan document root ke folder `public` jika memungkinkan.

## File konfigurasi yang tersedia

- `.env.example`: konfigurasi umum localhost.
- `.env.laragon.example`: konfigurasi siap pakai untuk Laragon dan SQLite.
- `.env.staging.example`: contoh server staging.
- `.env.production.example`: contoh server production.

## Paket deployment

Arsip ZIP dibuat tanpa `.env`, `.git`, `vendor`, `node_modules`, cache, session runtime, dan backup pribadi. Setelah mengekstrak arsip, dependency dipasang ulang melalui Composer/npm sesuai panduan tujuan instalasi.
