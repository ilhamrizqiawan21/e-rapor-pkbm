# E-Rapor PKBM

Fondasi aplikasi e-Rapor untuk PKBM kelas 1–12.

## Stack

- Laravel 13
- Livewire 4
- Blade
- Tailwind CSS 4
- Alpine.js dibundel melalui Vite
- SQLite untuk development awal; MySQL/PostgreSQL untuk deployment

## Menjalankan project

```bash
composer install
npm install
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`.

## Menjalankan browser test

Playwright menyiapkan database SQLite E2E terpisah (`database/database.e2e.sqlite`), menjalankan server Laravel pada port `8001`, lalu menguji Chromium desktop dan mobile.

```bash
npm run test:e2e
```

Untuk menjalankan dengan browser terlihat:

```bash
npm run test:e2e:headed
```

## Backup, restore, dan scheduler

Backup manual Admin tersedia di \`/admin/backups\`. Backup disimpan pada disk private, diberi timestamp dan checksum SHA-256, lalu dibersihkan sesuai \`BACKUP_RETENTION_DAYS\`. Restore wajib mengunggah file valid dan mengetik \`RESTORE\`; aplikasi membuat safety backup sebelum mengganti database dan menjalankan smoke test tabel inti setelahnya.

Untuk localhost/SQLite, jalankan \`php artisan backup:database\`.

Untuk online/MySQL atau PostgreSQL, pasang binary \`mysqldump\`/\`mysql\` atau \`pg_dump\`/\`psql\` pada server. Jalankan scheduler Laravel pada satu worker dengan \`php artisan schedule:work\`.

Jadwal default adalah setiap hari pukul \`02:30\` waktu aplikasi. Atur disk private, retensi, batas ukuran restore, lokasi binary, dan enkripsi melalui variabel \`BACKUP_*\`. Backup terenkripsi aktif secara default melalui \`BACKUP_ENCRYPTION=true\` dan \`APP_KEY\`; jangan memindahkan APP_KEY atau file backup melalui repository/chat.

Detail klasifikasi data, role boundary, retensi, dan respons insiden ada di [PRIVACY-AND-DATA-SECURITY.md](PRIVACY-AND-DATA-SECURITY.md).

## Konfigurasi lingkungan

Profil yang tersedia:

- `.env.example`: development localhost/LAN dengan SQLite file `database/database.sqlite`.
- `.env.testing.example`: PHPUnit dengan SQLite in-memory, cache/session array, queue sync, dan mail array.
- `.env.staging.example`: contoh staging online dengan MySQL, HTTPS, queue/cache/session database, dan SMTP.
- `.env.production.example`: contoh production online; semua credential dan `APP_KEY` wajib diisi pada server, bukan di-commit.

Konfigurasi aplikasi menggunakan timezone `Asia/Jakarta`, locale `id`, format tanggal `dd/mm/YYYY`, format waktu `dd/mm/YYYY HH:mm`, serta angka Indonesia dengan pemisah ribuan titik dan desimal koma.

Storage dibagi menjadi disk private (`local`), disk public (`public`), dan disk arsip PDF rapor (`local` secara default). Mail lokal memakai log, sedangkan staging/production memakai SMTP melalui environment server. Queue dan cache default memakai database agar kompatibel dengan localhost maupun deployment online.

## Browser minimum

Aplikasi membutuhkan JavaScript aktif dan mendukung dua versi mayor terbaru Chrome, Edge, Firefox, serta Safari. Browser lama di luar kebijakan tersebut tidak menjadi target dukungan MVP.

Data seed development menyediakan:

- PKBM Contoh
- Role Admin, Guru, Kepsek
- User demo `admin`, `guru`, dan `kepsek`
- Password demo: `password` untuk seluruh akun lokal

## Struktur fondasi

- `app/Models`: model domain PKBM dan rapor
- `database/migrations`: skema akademik, penempatan siswa, nilai, rapor, dan audit
- `resources/views/layouts`: shell UI awal
- `SCOPE-AND-GLOSSARY.md`: baseline scope, istilah canonical, role matrix, dan aturan bisnis
- `PRIVACY-AND-DATA-SECURITY.md`: klasifikasi data, privacy, retensi, dan checklist keamanan deployment
- `TODO.md`: master checklist eksekusi terukur
- Dokumen PRD, ERD, dan design berada di folder `outputs` workspace induk.

## Status

Project baru berada pada tahap fondasi. Modul master data, autentikasi UI, input nilai, rapor, dan leger akan dibangun bertahap.
