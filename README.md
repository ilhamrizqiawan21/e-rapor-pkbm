# E-Rapor PKBM

Fondasi aplikasi e-Rapor untuk PKBM kelas 1–12.

## Stack

- Laravel 13
- Livewire 4
- Blade
- Tailwind CSS 4
- Alpine.js melalui ekosistem Livewire saat dibutuhkan
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

Data seed development menyediakan:

- PKBM Contoh
- Role Admin, Guru, Kepsek
- User demo `admin@erapor.test`

## Struktur fondasi

- `app/Models`: model domain PKBM dan rapor
- `database/migrations`: skema akademik, penempatan siswa, nilai, rapor, dan audit
- `resources/views/layouts`: shell UI awal
- `SCOPE-AND-GLOSSARY.md`: baseline scope, istilah canonical, role matrix, dan aturan bisnis
- `TODO.md`: master checklist eksekusi terukur
- Dokumen PRD, ERD, dan design berada di folder `outputs` workspace induk.

## Status

Project baru berada pada tahap fondasi. Modul master data, autentikasi UI, input nilai, rapor, dan leger akan dibangun bertahap.
