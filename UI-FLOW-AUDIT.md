# UI & Flow Audit — E-Rapor PKBM

Tanggal audit: 2026-11-06
Reviewer: UI/flow/design reviewer
Scope: seluruh Blade/layout/component yang aktif, navigasi role Admin/Guru/Kepsek, viewport desktop dan mobile.

## Kriteria review

UI dinilai harus:

- modern tetapi tetap tenang dan utilitarian untuk pekerjaan administrasi sekolah;
- jelas, mudah dipahami, dan memiliki hierarki visual yang membantu pengguna menentukan langkah berikutnya;
- profesional untuk dipakai PKBM, bukan terasa seperti halaman demo atau scaffold;
- memiliki karakter E-Rapor PKBM melalui konteks periode, rombel, peserta didik, nilai, rapor, dan workflow review;
- konsisten pada spacing, typography, warna, status, navigasi, form feedback, dan responsivitas;
- menghindari pola AI-slop/generik: copy placeholder berulang, kartu dekoratif tanpa fungsi, gradient/blobs/stock imagery, dan showcase komponen yang bocor ke navigasi pengguna;
- dapat digunakan dengan keyboard dan pembaca layar pada fungsi dasar.

## Metode dan bukti

- Server lokal memakai fixture E2E SQLite pada `http://127.0.0.1:8002`.
- Role diuji dengan akun seeded `admin`, `guru`, dan `kepsek`.
- Viewport diuji pada 390×844 dan 1440×900.
- Menu smoke test: `BASE_URL=http://127.0.0.1:8002 npx playwright test tests/e2e/menu-navigation.spec.ts --reporter=line` — **6/6 lulus** untuk Admin/Guru/Kepsek desktop/mobile.
- Crawl GET menu Admin: semua menu yang tampil merespons HTTP 200 dan tidak mengarah ke login/server error.
- Batas akses teramati: Guru mendapat 403 untuk audit, backup, identitas PKBM, dan format rapor; Kepsek mendapat akses audit tetapi tidak backup/pengaturan.
- Pemeriksaan dasar DOM pada halaman yang diuji: input visible memiliki label/accessible name, button/link memiliki accessible name, dan tidak ada document-level horizontal overflow.
- Catatan: fixture E2E belum memiliki periode, rombel, peserta didik, assignment, atau rapor aktif. State populated untuk tabel dan workflow rapor belum dapat dinilai end-to-end tanpa menambah data uji.

## Status tindak lanjut

Audit P1 sudah ditindaklanjuti pada working build ini:

- P1-01: tabel utama memiliki presentasi card/list pada mobile; empty state dan aksi tetap terlihat.
- P1-02: drawer mobile memindahkan fokus, melakukan focus trap, mendukung Escape, mengunci scroll, dan mengembalikan fokus ke trigger.
- P1-03: state periode kosong memiliki CTA operasional sesuai role.
- P1-04: copy scaffold diganti dengan bahasa kerja PKBM dan status periode.
- P1-05: “Komponen UI” dikeluarkan dari navigasi production; route tetap tersedia untuk internal development.
- P1-06: input, select, dan textarea menghubungkan error/hint dengan `aria-describedby`, `aria-invalid`, serta `role="alert"`.

Validasi pasca-remediasi: PHPUnit 233/233, Playwright 67 passed dan 1 skipped (focus trap memang hanya relevan pada mobile), Pint, Vite build, cache Laravel, dan diff check lulus.

Screenshot bukti utama:

- [Dashboard Admin mobile](ui-audit-artifacts/dashboard-admin-mobile.png)
- [Dashboard Admin desktop](ui-audit-artifacts/dashboard-admin-desktop.png)
- [Dashboard Guru mobile](ui-audit-artifacts/dashboard-guru-mobile.png)
- [Dashboard Kepsek mobile](ui-audit-artifacts/dashboard-kepsek-mobile.png)
- [Peserta didik mobile](ui-audit-artifacts/admin-students-mobile.png)
- [Input nilai mobile](ui-audit-artifacts/admin-grade-entry-mobile.png)
- [Daftar rapor mobile](ui-audit-artifacts/admin-report-cards-mobile.png)
- [Histori audit mobile](ui-audit-artifacts/admin-audit-logs-mobile.png)
- [Backup mobile](ui-audit-artifacts/admin-backups-mobile.png)
- [Format rapor mobile](ui-audit-artifacts/admin-settings-format-mobile.png)

## Ringkasan prioritas

| Prioritas | Jumlah | Status |
|---|---:|---|
| P0 | 0 | Tidak ditemukan blocker yang membuat seluruh aplikasi tidak dapat dipakai |
| P1 | 6 | Perlu diperbaiki sebelum demo client/production karena berdampak pada flow, clarity, atau accessibility |
| P2 | 5 | Perbaikan polish, consistency, dan test coverage setelah P1 |

## Temuan P1

### P1-01 — Tabel data tidak memiliki presentasi mobile yang usable

- Route/surface: `/dashboard/admin`, `/dashboard/guru`, `/dashboard/kepsek`, `/admin/students`, `/admin/report-cards`, `/admin/audit-logs`, `/admin/backups`.
- Lokasi: [dashboard/admin.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/dashboard/admin.blade.php:5), [dashboard/guru.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/dashboard/guru.blade.php:5), [dashboard/kepsek.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/dashboard/kepsek.blade.php:5), [students/index.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/admin/students/index.blade.php:8), [report-cards/index.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/admin/report-cards/index.blade.php:18), [audit-logs/index.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/admin/audit-logs/index.blade.php:94), [backups/index.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/admin/backups/index.blade.php:42).
- Bukti terukur pada 390px:
  - tabel dashboard Admin memiliki `scrollWidth` 680px pada container 356px dan tabel transfer 560px;
  - dashboard Kepsek memakai tabel minimum 900px/760px;
  - rapor memakai `min-w-[980px]`, audit `min-w-[1100px]`, backup `min-w-[760px]`;
  - screenshot menunjukkan hanya kolom awal yang terbaca, nama actor terpotong, dan empty-state berada di area horizontal yang tidak terlihat.
- Dampak: pengguna mobile dapat menyimpulkan bahwa tabel kosong atau tidak memiliki aksi, padahal pesan empty-state/kolom aksi berada di luar viewport. Ini memutus flow utama review data dan rapor.
- Rekomendasi konkret:
  1. Untuk mobile, ubah tabel menjadi list/card per record dengan 2–3 field prioritas dan tombol aksi selalu terlihat.
  2. Jika tabel horizontal tetap diperlukan, tampilkan label eksplisit “Geser tabel ke kanan untuk melihat aksi”, `tabindex="0"`, `role="region"`, `aria-label`, dan indikator scroll yang terlihat.
  3. Jangan menaruh empty-state di `<tr>` pada tabel fixed-width mobile; tampilkan empty-state di luar tabel atau gunakan `colspan` pada layout yang tidak membutuhkan scroll.
  4. Pada audit/backup, prioritaskan waktu–aksi–entity dan pindahkan detail JSON/checksum ke drawer/detail row.

### P1-02 — Drawer navigasi mobile tidak mengelola fokus

- Route/surface: semua halaman authenticated pada viewport < `lg`.
- Lokasi: [layouts/app.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/layouts/app.blade.php:14-59).
- Bukti: setelah tombol “Buka menu navigasi” dibuka pada 390px, snapshot memberi `role="dialog"`, tetapi setelah menekan `Tab` fokus berpindah ke tombol akun pada konten di belakang drawer. Tidak ada `x-trap`, pemindahan fokus awal ke “Tutup menu”, `inert` pada main content, atau pengembalian fokus ke tombol pembuka.
- Dampak: keyboard user/screen-reader user dapat berinteraksi dengan konten di balik overlay; dialog tidak berperilaku sebagai modal yang terisolasi.
- Rekomendasi konkret:
  1. Saat drawer terbuka, simpan trigger element lalu fokuskan tombol “Tutup menu”.
  2. Trap `Tab` di drawer dan pulihkan fokus ke trigger saat ditutup.
  3. Beri `inert`/hidden accessibility tree pada konten utama selama drawer aktif.
  4. Pastikan Escape, klik backdrop, dan navigasi link menutup drawer serta mengembalikan fokus dengan konsisten.

### P1-03 — State “belum siap” belum memberi jalur setup yang dapat ditindaklanjuti

- Route/surface: `/`, `/dashboard/admin`, `/dashboard/guru`, `/dashboard/kepsek`, `/admin/grade-entry`.
- Lokasi: [dashboard/admin.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/dashboard/admin.blade.php:2-5), [dashboard/guru.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/dashboard/guru.blade.php:2-5), [dashboard/kepsek.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/dashboard/kepsek.blade.php:2-5), [grade-entry/index.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/admin/grade-entry/index.blade.php:49-50).
- Bukti: dashboard menampilkan “Aktifkan tahun pelajaran dan semester” tetapi alert tidak memiliki tombol/link; Input nilai menampilkan “Admin perlu memetakan mapel dan Guru terlebih dahulu” tetapi empty-state tidak menyediakan link ke setup yang relevan. Tombol “Tampilkan” tetap menjadi satu-satunya affordance pada form dengan pilihan kosong.
- Dampak: Admin/Guru mengetahui masalah tetapi tidak tahu urutan tindakan. Ini menghasilkan dead-end pada first-run onboarding dan demo client.
- Rekomendasi konkret:
  1. Buat checklist prasyarat yang menyatakan status Tahun Pelajaran → Semester → Kelas → Mapel → Assignment Guru → Peserta Didik.
  2. Beri CTA sesuai role: Admin ke modul setup yang belum selesai; Guru ke “Hubungi Admin/menunggu penugasan” atau dashboard; Kepsek ke “Menunggu persiapan Admin”.
  3. Nonaktifkan “Tampilkan” ketika konteks belum tersedia dan jelaskan alasannya lewat hint yang terhubung ke select.
  4. Setelah satu prasyarat selesai, tampilkan next step yang jelas, bukan kembali ke dashboard generik.

### P1-04 — Copy global masih mengkomunikasikan prototype/scaffold

- Route/surface: seluruh dashboard dan seluruh halaman authenticated.
- Lokasi: [layouts/app.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/layouts/app.blade.php:194-196), [dashboard/admin.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/dashboard/admin.blade.php:2), [dashboard/guru.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/dashboard/guru.blade.php:2), [dashboard/kepsek.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/dashboard/kepsek.blade.php:2).
- Bukti: header selalu menampilkan badge “Fondasi awal”; tiga dashboard mengulang “Fondasi project sudah siap.” Screenshot desktop/mobile memperlihatkan badge tersebut sebagai elemen global utama.
- Dampak: untuk client/operator, produk terasa belum selesai dan tidak memiliki karakter operasional E-Rapor PKBM. Ini bertentangan langsung dengan kriteria profesional dan bukan sekadar masalah copy kecil.
- Rekomendasi konkret:
  1. Ganti badge dengan status periode nyata: “Ganjil 2025/2026”, “Belum ada periode aktif”, atau indikator sinkronisasi terakhir.
  2. Pindahkan pesan onboarding ke banner khusus Admin yang hanya muncul ketika prasyarat memang belum lengkap.
  3. Ganti “Fondasi project sudah siap” dengan manfaat role-specific: “Pantau progres nilai dan rapor pada periode aktif.”
  4. Gunakan istilah PKBM yang konsisten: Peserta Didik, Program/Paket, Rombel, Periode, Leger, dan Workflow Rapor.

### P1-05 — Halaman internal “Komponen UI” bocor ke navigasi semua role

- Route/surface: `/components-showcase`; link “Komponen UI” pada sidebar mobile dan desktop.
- Lokasi: [layouts/app.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/layouts/app.blade.php:69,130), [routes/web.php](/home/ilhamzp/Projects/e-rapor-pkbm/routes/web.php:152), [components-showcase.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/components-showcase.blade.php:1).
- Bukti: crawl navigasi dan snapshot role menunjukkan link “Komponen UI” tampil pada Admin, Guru, dan Kepsek. Halaman berisi token warna, variasi button, contoh input, tabel demo, modal, toast, dan state contoh; ini jelas halaman internal design-system, bukan workflow operator.
- Dampak: navigasi terlihat seperti aplikasi demo/template dan mengurangi fokus pada pekerjaan PKBM. Ini juga melanggar prinsip menghindari UI generik/AI-slop yang dipajang sebagai fitur produk.
- Rekomendasi konkret:
  1. Hilangkan link dari navigasi production.
  2. Batasi route dengan `app()->environment('local')` atau permission developer-only.
  3. Jika perlu ditampilkan saat demo internal, pindahkan ke route `/dev/ui` dan beri label “Internal design system”.

### P1-06 — Error form belum konsisten terhubung ke field untuk assistive technology

- Surface: seluruh form yang memakai komponen `<x-select>` dan `<x-textarea>`.
- Lokasi: [components/select.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/components/select.blade.php:39-80), [components/textarea.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/components/textarea.blade.php:39-56). Pembanding yang sudah benar: [components/input.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/components/input.blade.php:46-84).
- Bukti source: input memberi `aria-invalid` dan `aria-describedby` saat error; select/textarea hanya merender teks error tanpa `aria-invalid`, `aria-describedby`, dan tanpa `role="alert"`/id error yang bisa dirujuk.
- Dampak: visual feedback terlihat oleh pengguna sighted, tetapi screen reader tidak selalu mengetahui field mana yang invalid atau pesan apa yang harus diperbaiki. Ini paling kritis pada setup akademik dan input rapor.
- Rekomendasi konkret:
  1. Samakan kontrak komponen input/select/textarea: id stabil, `aria-invalid="true"`, dan `aria-describedby="{id}-error"` saat error.
  2. Beri id pada pesan error dan `role="alert"` atau `aria-live="polite"` sesuai tingkat urgensi.
  3. Untuk select dependent yang kosong, gunakan `aria-describedby` ke hint prasyarat dan `disabled` jika memang belum dapat dipilih.

## Temuan P2

### P2-01 — Judul browser hampir semua halaman sama

- Route/surface: seluruh halaman app selain login/showcase yang memberi title eksplisit.
- Lokasi: [layouts/app.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/layouts/app.blade.php:6); route crawl menemukan halaman seperti `/admin/students`, `/admin/grade-entry`, `/admin/audit-logs`, dan `/admin/backups` semuanya bertitle `E-Rapor PKBM`.
- Dampak: multi-tab, bookmark, dan history browser sulit dipindai; pengguna tidak langsung tahu halaman mana yang sedang aktif.
- Rekomendasi: gunakan title kontekstual, misalnya `Peserta Didik · E-Rapor PKBM`, `Input Nilai · E-Rapor PKBM`, `Histori Audit · E-Rapor PKBM`.

### P2-02 — Active navigation belum konsisten dan belum memberi `aria-current`

- Lokasi: [layouts/app.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/layouts/app.blade.php:129-164).
- Bukti: pada `/admin/students`, query `nav a[aria-current]` mengembalikan 0 elemen. Hanya Dashboard dan Komponen UI yang diberi `aria-current` secara eksplisit; route master data lain hanya mengubah class visual.
- Dampak: pengguna keyboard/screen reader tidak mendapatkan penanda halaman aktif, dan active state visual desktop/mobile dapat berbeda.
- Rekomendasi: buat partial nav item yang selalu menerapkan class active dan `aria-current="page"` berdasarkan `request()->routeIs(...)`; gunakan partial yang sama untuk drawer mobile dan sidebar desktop.

### P2-03 — Kontrol icon-only pada modal/toast belum punya accessible name

- Lokasi: [components/modal.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/components/modal.blade.php:61-69), [components/toast.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/components/toast.blade.php:99-108).
- Bukti source: tombol close hanya berisi SVG tanpa `aria-label`; komponen toast juga tidak menyediakan label untuk tombol dismiss. Close modal belum memiliki `focus-visible:ring` yang konsisten dengan button utama.
- Dampak: ketika modal/toast benar-benar dipakai, screen reader akan membaca tombol tanpa nama atau nama yang tidak bermakna.
- Rekomendasi: tambahkan `aria-label="Tutup dialog"` dan `aria-label="Tutup notifikasi"`, `aria-hidden="true"` pada SVG, visible focus ring, serta fokus awal/return focus pada modal.

### P2-04 — Terminologi UI bercampur antara Indonesia, Inggris, dan jargon teknis

- Lokasi: [admin/audit-logs/index.blade.php](/home/ilhamzp/Projects/e-rapor-pkbm/resources/views/admin/audit-logs/index.blade.php:4-35), layout/dashboard, dan status badge.
- Bukti: halaman berlabel “Governance”, filter “Actor/Entity”, aksi “Login/Logout/Export/Restore”, sementara layar lain memakai “Peserta didik”, “Tahun pelajaran”, dan “Kepala PKBM”.
- Dampak: produk terasa seperti gabungan template admin dan aplikasi PKBM; hierarki konsep untuk operator non-teknis menjadi kurang konsisten.
- Rekomendasi: pilih kamus istilah produk dan terapkan konsisten: “Pengguna”, “Objek”, “Masuk”, “Keluar”, “Ekspor”, “Pulihkan”, “Tata kelola” atau istilah Indonesia lain yang disepakati client.

### P2-05 — State populated dan workflow mutasi belum terwakili oleh fixture UI audit

- Route/surface: `/admin/academic-years/{id}/semesters`, assignment mapel, `/admin/grade-entry` dengan siswa, `/admin/report-cards` dengan status DRAFT/IN_REVIEW/APPROVED/LOCKED, dan action transfer.
- Bukti: fixture E2E yang dipakai audit hanya memiliki predicate/program dasar; dashboard dan daftar utama didominasi empty state. Menu smoke 6/6 lulus, tetapi belum menguji tabel berisi data panjang, tombol aksi per row, badge status, pagination aktif, validasi konflik, atau review/lock flow pada data nyata.
- Dampak: belum ada kepastian visual saat konten terpadat—state yang paling penting untuk E-Rapor.
- Rekomendasi: tambahkan fixture “data-rich” minimal satu kelas, 5 peserta didik, 3 mapel, assignment guru, satu rapor per status workflow, transfer, dan audit log. Jalankan screenshot/keyboard review pada 390px, 768px, dan 1440px.

## Hal yang sudah baik

- Palet navy–teal, border, radius, dan spacing cukup konsisten; dashboard terlihat tenang dan profesional pada desktop.
- Hierarki dashboard role-specific sudah jelas: Admin operasional, Guru input nilai, Kepsek review/persetujuan.
- Form label dan accessible name dasar umumnya baik; pemeriksaan DOM tidak menemukan input visible tanpa label, button tanpa nama, atau gambar visible tanpa alt.
- Empty state component memiliki struktur visual yang jelas dan sudah mendukung action slot; masalah utamanya adalah beberapa halaman menaruh empty row di dalam tabel fixed-width.
- Error global menggunakan `role="alert"`, status sukses menggunakan `role="status"`, dan status badge membantu scanning workflow.
- Navigation smoke test enam kombinasi role/device lulus tanpa HTTP error.

## Urutan perbaikan yang disarankan untuk agent UI-fix

1. P1-01: desain ulang table-to-card mobile dan pastikan empty-state/action selalu terlihat.
2. P1-02: perbaiki focus management drawer dan modal.
3. P1-03: buat onboarding/prerequisite CTA berdasarkan role.
4. P1-04 dan P1-05: hilangkan bahasa scaffold serta keluarkan showcase internal dari production nav.
5. P1-06: samakan kontrak aksesibilitas error pada select/textarea.
6. P2-01 sampai P2-04: title, active nav, accessible label, dan kamus istilah.
7. P2-05: siapkan fixture data-rich lalu ulangi screenshot, keyboard, dan flow audit.

## Verdict setelah remediasi

**SHIP untuk demo client/internal dengan P1 tertutup.** UI kini lebih fokus pada pekerjaan PKBM, responsif pada mobile, punya jalur setup yang jelas, dan feedback form yang lebih aksesibel. P2-01 sampai P2-05 tetap menjadi backlog polish berikutnya, terutama title kontekstual, `aria-current`, label kontrol icon-only, kamus istilah, dan fixture data-rich untuk menguji workflow rapor yang sudah terisi.
