# Menu QA Report — Post UI Remediation

Tanggal audit: 2026-11-06
Target: Laravel E-Rapor PKBM lokal `http://127.0.0.1:8001` dengan database E2E ter-seed
Scope perubahan: hanya `tests/*` dan dokumen laporan ini.

## Ringkasan

| Area | Hasil | Catatan |
|---|---:|---|
| Menu Admin | 19/19 destination lulus | Tidak ada 4xx/5xx, redirect login, atau exception halaman |
| Menu Guru | 12/12 destination lulus | Menu Admin-only tidak tampil |
| Menu Kepsek | 13/13 destination lulus | Histori audit tampil; backup dan pengaturan Admin-only tidak tampil |
| Responsive drawer | Lulus desktop dan mobile | Drawer dapat dibuka dan tombol tutup dapat digunakan |
| Account menu | Lulus seluruh role dan viewport | Profil, ubah password, dan logout tampil |
| Empty/error state | Lulus | Tidak ditemukan `Server Error`, `Whoops`, atau `SQLSTATE` pada menu yang dibuka |
| Layout smoke | Lulus | Tidak ditemukan horizontal overflow pada viewport yang diuji |

Status keseluruhan: **SHIP** untuk navigasi dan fungsi pembukaan halaman. Tidak ditemukan blocker fungsional.

## Matriks menu per role

Status `200` berarti response akhir setelah redirect normal berhasil dibuka dan halaman tidak mengarah kembali ke `/login`.

| Group | Menu | Admin | Guru | Kepsek |
|---|---|---:|---:|---:|
| Umum | Dashboard | 200 | 200 | 200 |
| Master Data | Data Guru | 200 | — | — |
| Master Data | Peserta didik | 200 | 200 | 200 |
| Master Data | Mata pelajaran | 200 | 200 | 200 |
| Master Data | Bank deskripsi | 200 | 200 | 200 |
| Master Data | Rentang predikat | 200 | — | — |
| Master Data | Tahun pelajaran | 200 | 200 | 200 |
| Master Data | Program & tingkat | 200 | 200 | 200 |
| Master Data | Kelas / rombel | 200 | 200 | 200 |
| Penilaian | Input nilai | 200 | 200 | 200 |
| Rapor dan Leger | Kelengkapan rapor | 200 | 200 | 200 |
| Rapor dan Leger | Generator leger | 200 | 200 | 200 |
| Rapor dan Leger | Histori audit | 200 | — | 200 |
| Pengaturan PKBM | Identitas PKBM | 200 | — | — |
| Pengaturan PKBM | Penandatangan | 200 | — | — |
| Pengaturan PKBM | Format rapor | 200 | — | — |
| Pengaturan PKBM | Backup database | 200 | — | — |
| Akun | Profil saya | 200 | 200 | 200 |
| Akun | Ubah password | 200 | 200 | 200 |

`—` berarti menu memang tidak dirender untuk role tersebut, bukan route yang gagal.

Jumlah link unik yang ditemukan dari sidebar/drawer responsive:

- Admin: 19 link.
- Guru: 12 link.
- Kepsek: 13 link.

## Penilaian label, grouping, dan flow

| Aspek | Penilaian | Bukti / catatan |
|---|---|---|
| Label domain | Baik | “Peserta didik”, “Bank deskripsi”, “Input nilai”, “Kelengkapan rapor”, “Generator leger”, “Histori audit”, dan “Backup database” cukup spesifik dan mudah dipahami |
| Grouping | Baik | Urutan Data inti → Penilaian → Rapor & leger → Pengaturan PKBM → Akun konsisten dengan alur operasional PKBM |
| Role visibility | Baik | Data Guru, Rentang predikat, Pengaturan PKBM, dan Backup database dibatasi ke Admin; Histori audit tersedia untuk Admin/Kepsek |
| Account flow | Baik | Profil, ubah password, dan logout dipusatkan di account menu header; diuji pada desktop/mobile |
| Mobile flow | Baik | Drawer dapat dibuka melalui “Buka menu navigasi”, ditutup melalui “Tutup menu”, dan seluruh destination tetap dapat dibuka |
| Non-generik | Baik | Ikon domain, rail navy-teal, active marker, dan submenu bertahap menjaga sidebar tetap khas E-Rapor PKBM |
| Toggle | Lulus | Sidebar desktop dapat diciutkan ke rail ikon dan dibuka kembali; pilihan tersimpan di browser. Setiap kelompok memiliki `aria-expanded` dan submenu toggle |
| Istilah umum | Dapat diterima | “Dashboard” dan “Akun” bersifat umum, tetapi ditempatkan dalam konteks grouping yang jelas |

### Temuan UX non-blocking

- **Ditutup — Komponen UI dikeluarkan dari navigasi production.** Route showcase tetap tersedia untuk kebutuhan development, tetapi tidak lagi tampil sebagai menu kerja Admin/Guru/Kepsek.

Tidak ditemukan menu dengan label ambigu yang menghalangi alur, grouping yang salah urut, atau akses role yang membuka menu Admin-only.

## Test otomatis yang ditambahkan

### `tests/e2e/menu-navigation.spec.ts`

Test ini melakukan hal berikut untuk Admin, Guru, dan Kepsek pada Chromium desktop serta mobile Chrome:

1. Login dengan akun E2E masing-masing role.
2. Mengambil link aktual dari sidebar/drawer, lalu deduplikasi berdasarkan URL.
3. Membuka semua destination dan memeriksa response akhir.
4. Menolak status HTTP `>= 400`, redirect ke `/login`, serta halaman yang mengandung `Server Error`, `Whoops`, atau `SQLSTATE`.
5. Memastikan grouping dan label penting tersedia.
6. Menguji toggle submenu “Data inti” pada desktop dan mobile.
6. Memastikan menu Admin-only tidak muncul pada Guru/Kepsek.
7. Membuka drawer mobile dan memeriksa tombol tutup.
8. Membuka account menu dan memeriksa Profil, Ubah password, dan Keluar.
9. Memeriksa tidak ada horizontal overflow.

## Command dan hasil test

```bash
BASE_URL=http://127.0.0.1:8001 npx playwright test tests/e2e/menu-navigation.spec.ts --reporter=line
# 6 passed

BASE_URL=http://127.0.0.1:8001 npx playwright test --reporter=line
# 68 total: 67 passed, 1 skipped (desktop + mobile)

php artisan test --no-coverage
# 233 passed, 1,030 assertions
```

Visual regression berbasis baseline screenshot dan axe-core tidak dijalankan pada audit ini karena belum ada baseline visual/runner axe yang menjadi bagian dari suite. Sebagai pengganti yang terukur, audit ini menjalankan responsive drawer, label/grouping, destination smoke, exception detection, dan overflow smoke pada dua viewport.
