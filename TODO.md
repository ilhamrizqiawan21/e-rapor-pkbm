# TODO Master E-Rapor PKBM

Status: Baseline eksekusi v0.3 — scope dan istilah terkunci

Dokumen ini adalah checklist kerja utama project. Setiap item harus ditandai setelah benar-benar selesai dan diverifikasi. Tidak boleh melompat ke tahap berikutnya jika quality gate tahap sebelumnya belum lulus.

## Cara membaca status

- `[ ]` Belum dikerjakan
- `[-]` Sedang dikerjakan
- `[x]` Selesai dan sudah diverifikasi
- `[!]` Terblokir atau membutuhkan keputusan

## Aturan Definition of Done

Sebuah fitur hanya boleh dianggap selesai jika semua hal berikut terpenuhi:

- [ ] Migration/schema selesai dan dapat dijalankan ulang.
- [ ] Model, relasi, cast, dan fillable selesai.
- [ ] Authorization/pembatasan role selesai.
- [ ] UI lengkap untuk kondisi normal, kosong, loading, validasi gagal, dan error.
- [ ] Validasi server-side selesai.
- [ ] Validasi client-side tersedia bila membantu pengalaman pengguna.
- [ ] Test feature/unit yang relevan lulus.
- [ ] Seed/factory untuk skenario pengujian tersedia.
- [ ] Tidak ada error pada log aplikasi.
- [ ] Pint, test, dan build frontend lulus.
- [ ] Alur diverifikasi di localhost.
- [ ] Acceptance criteria tahap terpenuhi.
- [ ] Perubahan dicatat pada changelog atau commit.

## Quality gate global

- [ ] Tidak ada TODO kritis yang masih kosong sebelum rilis MVP.
- [ ] Semua role diuji dengan akun terpisah.
- [ ] Tidak ada user dapat melihat data di luar kewenangannya.
- [ ] Rapor dan leger mengambil sumber nilai yang sama.
- [ ] Rapor terkunci tidak dapat diubah tanpa proses unlock Admin.
- [ ] Perpindahan kelas meninggalkan riwayat yang dapat ditelusuri.
- [ ] Export PDF dan Excel berhasil untuk data kecil, sedang, dan besar.
- [ ] Backup dapat dibuat dan dipulihkan.
- [ ] Deployment online dan localhost terdokumentasi serta berhasil diuji.

---

# 00 Keputusan produk sebelum implementasi

## 00.01 Scope dan istilah

- [x] Tetapkan nama project: E-Rapor PKBM.
- [x] Tetapkan jenjang kelas 1–12.
- [x] Tetapkan format rapor sama untuk kelas 1–12.
- [x] Tetapkan mata pelajaran dapat dikustomisasi.
- [x] Tetapkan nilai 0–100.
- [x] Tetapkan KKM dan predikat tampil pada rapor.
- [x] Tetapkan deskripsi manual dan reusable.
- [x] Tetapkan nomor dokumen manual.
- [x] Tetapkan Peserta Didik dapat berpindah kelas.
- [x] Tetapkan Admin memiliki hak lock/unlock.
- [x] Tetapkan deployment online dan localhost.
- [x] Tetapkan role: Admin, Guru, Kepsek.
- [x] Istilah resmi peserta didik: Peserta Didik; “siswa” hanya singkatan percakapan.
- [x] Istilah resmi pengajar: Guru; “tutor” tidak dipakai sebagai label role.
- [x] Istilah resmi pimpinan: Kepala PKBM; kode role teknis `KEPSEK`.

## 00.02 Keputusan yang wajib dikunci

- [x] Predikat memakai rentang configurable dengan konfigurasi awal A/B/C/D.
- [x] Predikat dihitung otomatis dari rentang aktif; Guru tidak mengetik predikat secara terpisah pada alur normal.
- [x] KKM dapat berbeda per tahun pelajaran, semester, kelas, dan mata pelajaran.
- [x] Pindah kelas tengah semester memakai satu enrollment pelaporan yang dipilih Admin; tidak ada penggabungan otomatis dan tidak ada dua rapor resmi per semester.
- [x] Nomor dokumen diisi Guru wali kelas pada Draft dan dapat dikoreksi Admin sebelum lock.
- [x] Tanda tangan MVP memakai nama/jabatan dan aset gambar bila tersedia; tanda tangan elektronik tersertifikasi di luar MVP.
- [x] Rapor menggunakan A4 portrait.
- [x] Leger menggunakan Excel sebagai output utama dan PDF landscape sebagai output tambahan.
- [x] Format DOCX menjadi referensi struktur; boleh dirapikan agar stabil untuk PDF dan tetap mempertahankan komponen resmi.
- [x] Leger memiliki rata-rata pada sheet utama.
- [x] Leger utama berisi nilai dan ringkasan; KKM, predikat, dan status berada pada sheet detail.
- [x] Peringkat Peserta Didik tidak ditampilkan pada MVP.
- [x] PDF rapor diarsipkan saat Admin mengunci rapor.

## 00.03 Acceptance gate

- [x] Semua keputusan `00.02` memiliki jawaban tertulis.
- [x] Tidak ada istilah role yang berbeda antara UI, database, dan dokumen.
- [x] Skenario pindah kelas tengah semester memiliki contoh konkret dan hasil yang disepakati.

## 00.04 Baseline scope dan glossary

- [x] Sumber kebenaran scope/istilah dibuat di `SCOPE-AND-GLOSSARY.md`.
- [x] Scope MVP dan backlog pasca-MVP dipisahkan.
- [x] Role matrix Admin, Guru, dan Kepala PKBM ditetapkan.
- [x] Status workflow ditetapkan: `DRAFT`, `SUBMITTED`, `IN_REVIEW`, `NEEDS_REVISION`, `APPROVED`, `LOCKED`.
- [x] Mapping default Paket A/B/C ditetapkan tanpa memaksa template rapor berbeda.
- [x] Aturan nilai, KKM, predikat, deskripsi, perpindahan kelas, nomor dokumen, rapor, dan leger ditetapkan.
- [x] Skenario localhost/LAN dan online dipisahkan tanpa sinkronisasi dua arah pada MVP.
- [x] PRD, ERD, design, README, dan TODO merujuk pada baseline yang sama.

**Quality gate 00:** Lulus. Tahap berikutnya boleh dimulai dari autentikasi dan role setelah perubahan scope baru dicatat sebagai keputusan perubahan.

---

# 01 Fondasi repository dan lingkungan

## 01.01 Source control

- [x] Inisialisasi Git repository project.
- [x] Buat branch utama `main`.
- [x] Tambahkan `.gitattributes` dan `.gitignore` yang benar.
- [x] Pastikan `.env` tidak dilacak.
- [x] Pastikan `vendor/`, `node_modules/`, database SQLite lokal, dan hasil build sesuai kebijakan repository.
- [x] Buat commit baseline scaffold: `44bebae chore(repo): create initial Laravel foundation`.
- [x] Tetapkan format Conventional Commits melalui `.gitmessage` dan konfigurasi repository.

## 01.02 Toolchain

- [x] PHP tersedia.
- [x] Composer tersedia.
- [x] Node.js tersedia.
- [x] npm tersedia.
- [x] Laravel 13 terpasang.
- [x] Livewire 4 terpasang.
- [x] Tailwind CSS 4 terpasang.
- [x] Laravel Boost terpasang.
- [x] Tetapkan database development default: SQLite file `database/database.sqlite`.
- [x] Tetapkan database test: SQLite in-memory melalui `phpunit.xml` dan `.env.testing.example`.
- [x] Tetapkan versi browser minimum: dua versi mayor terbaru Chrome, Edge, Firefox, dan Safari dengan JavaScript aktif.

## 01.03 Konfigurasi aplikasi

- [x] Set nama aplikasi menjadi E-Rapor PKBM.
- [x] Set timezone aplikasi ke Asia/Jakarta.
- [x] Set locale aplikasi ke id.
- [x] Set format tanggal Indonesia: `d/m/Y`.
- [x] Set format angka dan desimal: ribuan titik, desimal koma, dua angka desimal.
- [x] Set `APP_URL` untuk localhost ke `http://localhost:8000`.
- [x] Siapkan `.env.example` lengkap.
- [x] Pisahkan konfigurasi local, test, staging, dan production melalui `.env*.example`.
- [x] Konfigurasi storage public/private dan disk arsip PDF.
- [x] Konfigurasi mail: log pada local, array pada test, SMTP pada staging/production.
- [x] Konfigurasi queue database untuk kebutuhan export.
- [x] Konfigurasi cache dan session database untuk online/localhost; test memakai array.

## 01.04 Fondasi UI

- [x] Buat layout aplikasi.
- [x] Buat sidebar desktop.
- [x] Buat header periode aktif.
- [x] Buat dashboard placeholder.
- [x] Tetapkan token warna final.
- [x] Tetapkan tipografi final.
- [x] Buat komponen button.
- [x] Buat komponen input.
- [x] Buat komponen select.
- [x] Buat komponen textarea.
- [x] Buat komponen table.
- [x] Buat komponen badge status.
- [x] Buat komponen modal.
- [x] Buat komponen toast.
- [x] Buat komponen pagination.
- [x] Buat empty state.
- [x] Buat loading state.
- [x] Buat error state.
- [x] Buat responsive drawer untuk tablet.

## 01.05 Quality gate fondasi

- [x] `php artisan migrate:fresh --seed` berhasil.
- [x] `npm run build` berhasil.
- [x] `php artisan test` berhasil pada baseline.
- [x] Dashboard mengembalikan HTTP 200.
- [ ] Repository baseline dapat di-clone dan dijalankan di komputer lain.

---

# 02 Autentikasi dan role

## 02.01 Database dan model

- [x] Gunakan auth custom minimal berbasis session web Laravel; starter kit tidak diperlukan pada MVP.
- [x] Tambahkan field status user: `ACTIVE`/`INACTIVE` dengan enum model.
- [x] Tambahkan relasi user ke institution.
- [x] Buat role Admin (`ADMIN`).
- [x] Buat role Guru (`GURU`).
- [x] Buat role Kepala PKBM (`KEPSEK`).
- [x] Buat pivot `user_roles` sehingga satu user dapat memiliki lebih dari satu role.
- [x] Buat staff profile untuk data Guru/Kepala PKBM.
- [x] Tambahkan `employee_number` untuk NIP/NIY/nomor pegawai sesuai kebutuhan PKBM.
- [x] Tambahkan factory user dengan state `withRole()` dan `inactive()`.
- [x] Tambahkan seeder tiga role.
- [x] Tambahkan seeder user demo per role: `admin`, `guru`, `kepsek`.

**Gate 02.01:** Lulus. Skema akun dan role siap dipakai tahap alur autentikasi 02.02.

## 02.02 Alur auth

- [x] Halaman login.
- [x] Validasi username/email wajib.
- [x] Validasi password wajib.
- [x] Pesan error login gagal.
- [x] Logout.
- [x] Redirect setelah login berdasarkan role.
- [x] Redirect user yang belum login.
- [x] Redirect user yang tidak memiliki akses melalui role middleware.
- [x] Nonaktifkan login user berstatus inactive.
- [x] Rate limit login lima percobaan per menit per login/IP.
- [x] Session regeneration setelah login.
- [x] Logout meng-invalidasi session dan meregenerasi CSRF token.
- [x] Password reset minimal: Admin dapat mereset password pengguna satu institusi; tersedia juga reset link berbasis email.
- [x] Ubah password sendiri dengan verifikasi password saat ini.

**Gate 02.02:** Lulus. Alur autentikasi session web siap dipakai untuk implementasi authorization 02.03.

## 02.03 Authorization

- [x] Middleware `authenticated` melalui middleware bawaan Laravel pada seluruh route privat.
- [x] Middleware `role` dengan alias `role:ADMIN|GURU|KEPSEK`.
- [x] Policy `Institution`.
- [x] Policy `AcademicClass`.
- [x] Policy `Student`.
- [x] Policy `SubjectAssignment`.
- [x] Policy `ReportCard`.
- [x] Admin dapat semua data dalam institusinya, tanpa bypass lintas institusi.
- [x] Guru hanya dapat melihat kelas, Peserta Didik, dan penugasan yang terkait dengannya.
- [x] Kepala PKBM hanya dapat melihat data institusinya dan melakukan review/approve rapor.
- [x] Guru wali kelas dapat mengakses kelas yang ditugaskan sebagai wali.
- [x] Guru tidak dapat lock/unlock.
- [x] Kepala PKBM tidak dapat lock/unlock.
- [x] Admin dapat lock/unlock rapor sesuai status workflow.

**Gate 02.03:** Lulus. Authorization policy dan role boundary siap dipakai modul master data serta workflow rapor.

## 02.04 UI auth

- [x] Layout login.
- [x] State loading login.
- [x] State error login.
- [x] Toggle tampil/sembunyikan password.
- [x] Link lupa password.
- [x] Menu profile.
- [x] Menu ubah password.
- [x] Menu logout.
- [x] Tampilan role aktif.

**Gate 02.04:** Lulus. UI autentikasi memiliki state normal, loading, error, toggle password, profil, logout, dan role aktif untuk desktop maupun mobile.

## 02.05 Tests

- [x] Guest tidak dapat membuka dashboard.
- [x] Login valid berhasil untuk Admin, Guru, dan Kepala PKBM.
- [x] Login invalid gagal dan menampilkan pesan error.
- [x] User inactive ditolak.
- [x] Admin dapat membuka route Admin.
- [x] Guru ditolak dari route Admin.
- [x] Kepala PKBM dapat membuka dashboard role/review entry.
- [x] Guru tidak dapat lock report card melalui authorization policy.
- [x] Kepala PKBM tidak dapat unlock report card melalui authorization policy.
- [x] Logout menghapus session.

**Catatan test:** Alur browser yang sudah tersedia diverifikasi Playwright pada Chromium desktop dan mobile. Skenario lock/unlock report card diverifikasi pada `AuthorizationPolicyTest` karena UI workflow report card belum dibangun.

**Gate 02.05:** Lulus. 20 Playwright test desktop/mobile dan seluruh test authorization terkait lulus.

## 02.06 Gate autentikasi

- [x] Tiga akun role dapat login.
- [x] Setiap role melihat entry dashboard dan label role yang tepat.
- [x] Semua entry route privat terlindungi dan guest diarahkan ke login.
- [x] Test auth dan authorization lulus.
- [x] Tidak ada akses silang antar institusi.

**Gate 02.06:** Lulus. Autentikasi, role boundary, proteksi route privat, dan isolasi data antar institusi telah diverifikasi melalui PHPUnit serta Playwright desktop/mobile.

---

# 03 Institusi dan pengaturan PKBM

## 03.01 Institusi

- [x] CRUD identitas PKBM.
- [x] Nama PKBM.
- [x] Alamat.
- [x] Nomor telepon.
- [x] Email.
- [x] Logo.
- [x] NPSN/nomor izin bila diperlukan.
- [x] Kode pos.
- [x] Validasi format email.
- [x] Validasi ukuran dan tipe logo.
- [x] Preview logo.
- [x] Hapus/ganti logo.

## 03.02 Penandatangan

- [x] Data Kepala PKBM.
- [x] Nama tampil pada preview rapor.
- [x] Jabatan tampil pada preview rapor.
- [x] NIP/NIY jika dipakai.
- [x] Guru wali kelas diambil dari user Guru melalui snapshot service.
- [x] Nama orang tua/wali diambil dari data Peserta Didik melalui snapshot service.
- [x] Snapshot penandatangan saat rapor dikunci melalui `ReportSignatureSnapshotService`.

## 03.03 Pengaturan format

- [x] Format nama Peserta Didik.
- [x] Format nomor dokumen.
- [x] Format tanggal Indonesia.
- [x] Setting tanda tangan.
- [x] Setting logo pada rapor.
- [x] Setting margin PDF.
- [x] Setting header/footer.
- [x] Setting tampil/tidaknya KKM.
- [x] Setting tampil/tidaknya predikat.

## 03.04 Gate institusi

- [x] Admin dapat menyimpan identitas PKBM.
- [x] Identitas muncul pada preview identitas rapor.
- [x] Logo tampil tanpa merusak layout.
- [x] Perubahan institusi tercatat pada audit log.

**Gate 03:** Lulus. Identitas PKBM, penandatangan, konfigurasi format, preview identitas rapor, logo, dan audit perubahan sudah tersedia. Isi akademik rapor akan diisi pada modul rapor berikutnya.

---

# 04 Periode akademik, program, tingkat, dan kelas

## 04.01 Tahun pelajaran

- [x] CRUD tahun pelajaran.
- [x] Field nama, tanggal mulai, tanggal selesai.
- [x] Status draft/aktif/ditutup.
- [x] Hanya satu tahun pelajaran aktif per institusi.
- [x] Tidak dapat menghapus periode yang memiliki data.
- [x] Konfirmasi penutupan periode.

## 04.02 Semester

- [x] CRUD semester.
- [x] Semester Ganjil.
- [x] Semester Genap.
- [x] Urutan semester.
- [x] Status draft/aktif/ditutup.
- [x] Hanya semester aktif yang dapat menerima input nilai.

## 04.03 Program dan tingkat

- [x] CRUD program.
- [x] Seed Paket A.
- [x] Seed Paket B.
- [x] Seed Paket C.
- [x] CRUD tingkat 1–12.
- [x] Validasi tingkat antara 1 dan 12.
- [x] Mapping tingkat ke program.
- [x] Opsi nama tingkat custom.

## 04.04 Kelas/rombel

- [x] CRUD kelas.
- [x] Kode kelas.
- [x] Nama kelas.
- [x] Tahun pelajaran.
- [x] Program.
- [x] Tingkat.
- [x] Guru wali kelas.
- [x] Status aktif/nonaktif.
- [x] Daftar siswa per kelas.
- [x] Daftar mapel per kelas.
- [x] Progress kelengkapan rapor kelas.
- [x] Tidak boleh membuat kode kelas duplikat pada periode sama.

## 04.05 Gate periode

- [x] Admin dapat membuat periode dan semester.
- [x] Admin dapat membuat kelas 1–12.
- [x] Guru hanya melihat periode/kelas yang ditugaskan.
- [x] Periode tertutup menolak perubahan akademik biasa.
- [x] Test uniqueness periode dan kelas lulus.

**Gate 04:** Lulus. Periode akademik, semester, program Paket A/B/C, tingkat 1–12, dan kelas/rombel sudah tersedia dengan pembatasan akses dan perlindungan periode tertutup.

---

# 05 Master Guru dan penugasan

## 05.01 Data Guru

- [x] CRUD user Guru.
- [x] Nama lengkap.
- [x] Email/username.
- [x] NIP/NIY/nomor identitas.
- [x] Nomor telepon.
- [x] Status aktif/nonaktif.
- [x] Reset password oleh Admin.
- [x] Aktivasi/nonaktivasi akun.
- [x] Filter Guru aktif/nonaktif.

## 05.02 Wali kelas

- [x] Pilih Guru wali kelas pada kelas.
- [x] Satu kelas memiliki maksimal satu wali kelas aktif.
- [x] Satu Guru dapat menjadi wali beberapa kelas jika diizinkan.
- [x] Riwayat wali kelas disimpan bila berubah.
- [x] Guru wali kelas dapat membuka kelengkapan rapor kelas.

## 05.03 Penugasan mapel

- [x] Assign Guru ke mapel pada kelas.
- [x] Satu mapel pada satu kelas memiliki Guru utama.
- [x] Validasi Guru aktif.
- [x] Validasi mapel aktif.
- [x] KKM per assignment.
- [x] Urutan mapel per assignment.
- [x] Aktivasi/nonaktivasi assignment.
- [x] Tampilan daftar assignment Guru.
- [x] Tampilan daftar assignment kelas.

## 05.04 Gate Guru

- [x] Admin dapat membuat akun Guru.
- [x] Admin dapat menetapkan wali kelas.
- [x] Admin dapat menetapkan Guru mapel.
- [x] Guru hanya melihat assignment sendiri.
- [x] Perubahan assignment tidak merusak nilai terkunci.

**Gate 05:** Lulus. Master Guru, histori wali kelas, penugasan Guru–mapel–kelas, KKM, filter akses Guru, reset password, dan proteksi nilai terkunci sudah tersedia. Master mata pelajaran tetap dikerjakan pada Tahap 07.

---

# 06 Master peserta didik dan penempatan

## 06.01 Data peserta didik

- [x] CRUD siswa.
- [x] NIPD/student number.
- [x] NISN/national student number.
- [x] Nama lengkap.
- [x] Jenis kelamin.
- [x] Tempat lahir.
- [x] Tanggal lahir.
- [x] Alamat.
- [x] Nama orang tua/wali.
- [x] Status aktif/nonaktif/lulus/pindah.
- [x] Search berdasarkan nama, NIPD, NISN.
- [x] Filter status.
- [x] Detail siswa.
- [x] Edit siswa.
- [x] Nonaktifkan siswa tanpa menghapus histori.

## 06.02 Import siswa

- [x] Template Excel siswa.
- [x] Download template.
- [x] Upload file.
- [x] Validasi header.
- [x] Validasi baris kosong.
- [x] Validasi NIPD duplikat.
- [x] Validasi tanggal lahir.
- [x] Preview data sebelum import.
- [x] Ringkasan sukses/gagal per baris.
- [x] Import hanya membuat data valid.
- [x] Download error report.

## 06.03 Enrollment awal

- [x] Tambahkan siswa ke kelas.
- [x] Tanggal mulai.
- [x] Status active.
- [x] Satu siswa tidak boleh memiliki dua penempatan aktif bersamaan.
- [x] Daftar siswa kelas.
- [x] Keluarkan siswa dari kelas dengan alasan.

## 06.04 Perpindahan kelas

- [x] Form kelas asal.
- [x] Form kelas tujuan.
- [x] Tanggal efektif.
- [x] Alasan.
- [x] Catatan.
- [x] Admin sebagai approval.
- [x] Tutup enrollment lama.
- [x] Buat enrollment baru.
- [x] Buat class transfer.
- [x] Timeline histori siswa.
- [x] Filter histori berdasarkan tahun pelajaran.
- [x] Pilihan sumber rapor untuk transfer tengah semester.
- [x] Audit transfer.
- [x] Tidak ada hard delete enrollment lama.

## 06.05 Gate siswa

- [x] CRUD siswa lulus.
- [x] Import Excel lulus dengan data valid dan invalid.
- [x] Siswa dapat ditempatkan ke kelas.
- [x] Siswa dapat dipindahkan tanpa kehilangan histori.
- [x] Test benturan enrollment lulus.
- [x] Test transfer tengah semester lulus.

**Gate 06:** Lulus. Master peserta didik, import XLSX/CSV dengan preview dan error report, enrollment, transfer kelas, histori, audit, serta pilihan sumber rapor sudah tersedia.

---

# 07 Master mata pelajaran dan KKM

## 07.01 Mata pelajaran

- [x] CRUD mapel.
- [x] Kode mapel.
- [x] Nama mapel.
- [x] Nama singkat mapel.
- [x] Kategori mapel.
- [x] Urutan default.
- [x] Status aktif/nonaktif.
- [x] Validasi kode unik per institusi.
- [x] Validasi nama singkat wajib untuk leger.
- [x] Preview header leger.

## 07.02 Mapping mapel

- [x] Mapping mapel ke kelas.
- [x] Tambah mapel dari kelas.
- [x] Hapus mapel dari kelas tanpa menghapus master.
- [x] Atur urutan mapel.
- [x] Atur Guru pengampu.
- [x] Atur KKM.
- [x] Salin konfigurasi mapel dari kelas lain.
- [x] Bulk add mapel ke beberapa kelas.
- [x] Bulk assign Guru.

## 07.03 KKM dan predikat

- [x] Input KKM 0–100.
- [x] Validasi KKM 0–100.
- [x] Simpan KKM sebagai assignment.
- [x] Konfigurasi rentang predikat.
- [x] Validasi rentang tidak overlap.
- [x] Preview konversi nilai ke predikat.
- [x] Snapshot KKM saat report grade dibuat.
- [x] Snapshot predikat saat report grade dibuat.

## 07.04 Gate mapel

- [x] Mapel dapat ditambah.
- [x] Mapel dapat dikurangi dari kelas.
- [x] Mapel dapat diurutkan.
- [x] KKM tampil pada input nilai.
- [x] Nama singkat tampil pada leger.
- [x] Perubahan master tidak mengubah rapor terkunci.

**Gate 07:** Lulus. Master mapel, mapping kelas, bulk operation, KKM, rentang predikat, dan snapshot rapor sudah tersedia.

---

# 08 Bank deskripsi reusable

## 08.01 Data snippet

- [x] CRUD snippet.
- [x] Judul snippet.
- [x] Isi snippet.
- [x] Mapping mapel opsional.
- [x] Owner Guru opsional.
- [x] Visibility private/shared.
- [x] Status aktif/nonaktif.
- [x] Search judul dan isi.
- [x] Filter mapel.
- [x] Filter milik saya/dibagikan.

## 08.02 Pemakaian snippet

- [x] Tombol buka bank deskripsi pada input nilai.
- [x] Preview snippet.
- [x] Insert snippet ke deskripsi.
- [x] Deskripsi siswa dapat diedit setelah insert.
- [x] Perubahan deskripsi siswa tidak mengubah snippet asli.
- [x] Simpan deskripsi siswa sebagai snippet baru.
- [x] Permission snippet private.
- [x] Permission snippet shared.

## 08.03 Gate deskripsi

- [x] Guru dapat membuat snippet private.
- [x] Admin dapat membuat snippet shared.
- [x] Guru dapat menggunakan snippet shared.
- [x] Guru tidak dapat mengubah snippet milik Guru lain tanpa izin.
- [x] Nilai rapor menyimpan teks final, bukan hanya referensi snippet.

**Gate 08:** Lulus. Bank deskripsi reusable, permission private/shared, picker, insert editable, serta snapshot teks final pada nilai sudah tersedia.

---

# 09 Input nilai dan penilaian

## 09.01 Halaman input nilai

- [x] Pilih tahun pelajaran.
- [x] Pilih semester.
- [x] Pilih kelas.
- [x] Pilih mapel.
- [x] Tampilkan Guru pengampu.
- [x] Tampilkan KKM.
- [x] Tampilkan status input.
- [x] Search siswa.
- [x] Pagination atau virtual table bila diperlukan.
- [x] Kolom nilai.
- [x] Kolom predikat.
- [x] Kolom deskripsi.
- [x] Tombol simpan.
- [x] Tombol simpan semua.
- [x] Tombol ajukan.
- [x] Indikator perubahan belum disimpan.

## 09.02 Validasi nilai

- [x] Nilai wajib jika mapel wajib.
- [x] Nilai numeric.
- [x] Nilai minimal 0.
- [x] Nilai maksimal 100.
- [x] KKM tampil sebagai referensi.
- [x] Predikat sesuai konfigurasi.
- [x] Deskripsi wajib jika kebijakan mewajibkan.
- [x] Error ditampilkan per siswa.
- [x] Error tidak menghapus input baris lain.
- [x] Duplicate grade ditolak.

## 09.03 Concurrency

- [x] Dua Guru dapat mengisi mapel berbeda pada kelas sama.
- [x] Update Guru A tidak menimpa mapel Guru B.
- [x] Timestamp updated_at terlihat saat konflik.
- [x] Warning jika data berubah setelah halaman dibuka.
- [x] Strategi optimistic locking atau last-write warning ditetapkan.

## 09.04 Submit workflow

- [x] Draft.
- [x] Submit oleh Guru.
- [x] Review oleh Guru wali kelas/Kepsek.
- [x] Reject dengan alasan.
- [x] Revisi kembali ke Guru.
- [x] Approve oleh Kepsek.
- [x] Lock oleh Admin.

## 09.05 Gate nilai

- [x] Guru dapat mengisi satu mapel end-to-end.
- [x] Guru tidak dapat mengisi mapel yang bukan assignment.
- [x] Nilai invalid ditolak.
- [x] Deskripsi reusable bekerja.
- [x] Submit/reject/approve bekerja.
- [x] Test concurrency minimal dua assignment lulus.

**Gate 09:** Lulus. Input nilai, validasi 0–100, save row/save-all, optimistic concurrency, workflow submit/review/reject/approve/lock, dan integrasi bank deskripsi sudah tersedia.

---

# 10 Kelengkapan rapor

## 10.01 Identitas dan nomor dokumen

- [x] Tampilkan identitas siswa.
- [x] Tampilkan NIPD/NISN.
- [x] Tampilkan kelas.
- [x] Tampilkan semester.
- [x] Tampilkan tahun pelajaran.
- [x] Tampilkan identitas PKBM.
- [x] Input nomor dokumen manual.
- [x] Validasi nomor dokumen sesuai kebijakan.
- [x] Snapshot identitas saat lock.

## 10.02 Sikap

- [x] Input sikap spiritual.
- [x] Input sikap sosial.
- [x] Deskripsi manual.
- [x] Bank deskripsi dapat dipakai.
- [x] Validasi komponen wajib.
- [x] Permission Guru wali kelas.

## 10.03 Absensi

- [x] Input sakit.
- [x] Input izin.
- [x] Input tanpa keterangan.
- [x] Nilai integer tidak negatif.
- [x] Tampilkan satuan hari.
- [x] Validasi angka besar yang tidak wajar.

## 10.04 Ekstrakurikuler

- [x] Tambah baris kegiatan.
- [x] Nama kegiatan.
- [x] Keterangan.
- [x] Urutkan baris.
- [x] Hapus baris kosong.
- [x] Empty state jika tidak ada kegiatan.

## 10.05 Catatan wali kelas

- [x] Input catatan/saran.
- [x] Bank deskripsi opsional.
- [x] Counter karakter.
- [x] Preview catatan pada rapor.

## 10.06 Gate kelengkapan

- [x] Progress per siswa.
- [x] Progress per kelas.
- [x] Komponen kosong teridentifikasi.
- [x] Sistem menolak approve jika wajib kosong.
- [x] Guru wali kelas dapat memperbaiki data.

**Gate 10:** Lulus. Identitas dan snapshot nomor dokumen, sikap, absensi, ekstrakurikuler, catatan wali kelas, progress kelengkapan, dan blokir approval untuk komponen wajib sudah tersedia.

---

# 11 Workflow approve, lock, unlock, audit

## 11.01 State machine

- [x] DRAFT.
- [x] SUBMITTED.
- [x] IN_REVIEW.
- [x] NEEDS_REVISION.
- [x] APPROVED.
- [x] LOCKED.
- [x] Unlock kembali ke DRAFT.
- [x] Semua transisi memiliki actor.
- [x] Semua transisi memiliki timestamp.
- [x] Reject memiliki alasan.
- [x] Unlock memiliki alasan.

## 11.02 Permission state

- [x] Guru dapat edit DRAFT miliknya.
- [x] Guru dapat submit.
- [x] Guru wali kelas dapat review kelengkapan.
- [x] Kepsek dapat approve/reject.
- [x] Admin dapat lock.
- [x] Admin dapat unlock.
- [x] Data LOCKED tidak dapat diedit oleh Guru.
- [x] Data LOCKED tidak dapat diedit oleh Kepsek.
- [x] Admin unlock harus melalui modal alasan.

## 11.03 Audit log

- [x] CREATE.
- [x] UPDATE.
- [x] SUBMIT.
- [x] REVIEW.
- [x] APPROVE.
- [x] REJECT.
- [x] LOCK.
- [x] UNLOCK.
- [x] TRANSFER.
- [x] Simpan actor.
- [x] Simpan entity.
- [x] Simpan nilai lama.
- [x] Simpan nilai baru.
- [x] Simpan alasan.
- [x] Halaman lihat histori.
- [x] Filter berdasarkan actor, action, tanggal, entity.

## 11.04 Gate workflow

- [x] Tidak ada transisi ilegal.
- [x] Unlock selalu tercatat.
- [x] Audit tidak dapat diubah melalui UI biasa.
- [x] Test seluruh transisi lulus.

**Gate 11:** Lulus. State machine, permission state, audit immutable, histori dengan filter, dan modal unlock beralasan sudah tersedia.

---

# 12 Generator rapor PDF

## 12.01 Template

- [x] Pilih engine PDF.
- [x] Buat template halaman identitas.
- [x] Buat tabel capaian kompetensi.
- [x] Buat tabel sikap.
- [x] Buat tabel ekstrakurikuler.
- [x] Buat tabel absensi.
- [x] Buat catatan/saran.
- [x] Buat area tanda tangan.
- [x] Buat nomor dokumen.
- [x] Buat header/footer.
- [x] Buat halaman nomor jika diperlukan.

## 12.02 Layout dan overflow

- [x] Deskripsi satu baris.
- [x] Deskripsi multi-baris.
- [x] Deskripsi sangat panjang.
- [x] Mapel sedikit.
- [x] Mapel banyak.
- [x] Ekstrakurikuler kosong.
- [x] Ekstrakurikuler banyak.
- [x] Catatan kosong.
- [x] Catatan panjang.
- [x] Nama siswa panjang.
- [x] Nama PKBM panjang.
- [x] Logo besar/kecil.
- [x] Halaman terpisah tidak memotong tabel.

## 12.03 Preview dan export

- [x] Preview browser.
- [x] Download satu rapor.
- [x] Cetak satu rapor.
- [x] Generate batch satu kelas.
- [x] Nama file konsisten.
- [x] PDF tidak dapat dibuat dari rapor belum lengkap.
- [x] PDF mengambil snapshot rapor terkunci.
- [x] Error export tampil ramah.

## 12.04 Gate PDF

- [x] Hasil PDF dibandingkan dengan DOCX referensi.
- [x] Tidak ada clipping.
- [x] Tidak ada overlap.
- [x] Tidak ada tabel keluar halaman.
- [x] Semua tanda tangan berada pada posisi benar.
- [x] PDF dapat dibuka pada browser dan PDF reader.

**Gate 12:** Lulus. Engine headless Chrome, preview/download/print, batch ZIP, snapshot rapor terkunci, proteksi kelengkapan, dan template A4 portrait sudah tersedia.

---

# 13 Generator leger Excel dan PDF

## 13.01 Format leger utama

- [x] Header PKBM.
- [x] Program.
- [x] Kelas.
- [x] Semester.
- [x] Tahun pelajaran.
- [x] Guru wali kelas.
- [x] No.
- [x] NIPD/NISN.
- [x] Nama Peserta Didik.
- [x] Kolom nilai per mapel memakai nama singkat.
- [x] Rata-rata per Peserta Didik.
- [x] Sakit.
- [x] Izin.
- [x] Tanpa keterangan.
- [x] Keterangan.
- [x] Footer tanggal/cetak.
- [x] Footer tanda tangan bila disepakati.

## 13.02 Sheet detail

- [x] Detail mapel.
- [x] KKM.
- [x] Nilai.
- [x] Predikat.
- [x] Status nilai.
- [x] Guru pengampu.
- [x] Deskripsi opsional.
- [x] Metadata periode.

## 13.03 Excel

- [x] Export satu kelas.
- [x] Export semua kelas sesuai permission.
- [x] Nama file.
- [x] Nama sheet aman dari karakter invalid.
- [x] Header freeze.
- [x] Auto width.
- [x] Number format nilai.
- [x] Border dan alignment.
- [x] Row average.
- [x] Summary sheet.
- [x] Data kosong ditandai.

## 13.04 PDF leger

- [x] Landscape.
- [x] Repeat header.
- [x] Tabel banyak mapel.
- [x] Page break aman.
- [x] Font terbaca.
- [x] Export satu kelas.
- [x] Export seluruh kelas.

## 13.05 Gate leger

- [x] Nilai leger sama dengan nilai rapor.
- [x] Nilai kosong terdeteksi.
- [x] Mapel memakai nama singkat.
- [x] KKM/predikat tersedia sesuai keputusan.
- [x] Export Excel bisa dibuka.
- [x] Export PDF bisa dibuka.

**Gate 13:** Lulus. Export leger Excel native dependency-free dan PDF landscape tersedia untuk satu kelas maupun seluruh kelas sesuai permission, dengan sumber nilai hanya dari rapor APPROVED/LOCKED.

---

# 14 Dashboard dan monitoring

## 14.01 Dashboard Admin

- [x] Jumlah kelas.
- [x] Jumlah siswa.
- [x] Jumlah Guru.
- [x] Periode aktif.
- [x] Progress nilai per kelas.
- [x] Rapor menunggu review.
- [x] Rapor siap lock.
- [x] Rapor terkunci.
- [x] Transfer kelas terbaru.
- [x] Link tindakan cepat.

## 14.02 Dashboard Guru

- [x] Assignment mapel.
- [x] Progress input per mapel.
- [x] Draft belum submit.
- [x] Revisi dari review.
- [x] Kelas wali.
- [x] Shortcut bank deskripsi.

## 14.03 Dashboard Kepsek

- [x] Rapor menunggu review.
- [x] Rapor disetujui.
- [x] Rapor ditolak.
- [x] Ringkasan per kelas.
- [x] Link preview.
- [x] Link approve/reject.

## 14.04 Gate dashboard

- [x] Semua angka berasal dari query aktual.
- [x] Dashboard menghormati permission.
- [x] Empty state tersedia.
- [x] Dashboard tidak gagal jika belum ada periode.

**Gate 14:** Lulus. Dashboard Admin, Guru, dan Kepala PKBM memakai query aktual, dibatasi institusi/role, memiliki empty state, dan teruji pada data maupun periode kosong.

---

# 15 Backup, restore, dan audit operasional

## 15.01 Backup

- [x] Tentukan format backup database.
- [x] Backup manual oleh Admin.
- [x] Backup otomatis online.
- [x] Nama file backup konsisten.
- [x] Timestamp backup.
- [x] Penyimpanan backup aman.
- [x] Retensi backup.
- [x] Notifikasi backup berhasil/gagal.

## 15.02 Restore

- [x] Restore di environment staging.
- [x] Validasi file backup.
- [x] Konfirmasi destructive restore.
- [x] Log actor restore.
- [x] Smoke test setelah restore.
- [x] Dokumentasikan prosedur restore.

## 15.03 Audit

- [x] Audit login.
- [x] Audit perubahan role.
- [x] Audit perubahan siswa.
- [x] Audit transfer kelas.
- [x] Audit perubahan nilai.
- [x] Audit lock/unlock.
- [x] Audit export.
- [x] Filter dan pagination audit.

## 15.04 Gate operasional

- [x] Backup dapat dibuat.
- [x] Backup dapat dipulihkan di staging.
- [x] Data setelah restore konsisten.
- [x] Audit mencatat aktivitas kritis.

**Gate 15:** Lulus. Backup SQLite lokal dan dump MySQL/PostgreSQL online tersedia, restore memakai konfirmasi dan safety backup, smoke test tabel inti dijalankan, serta aktivitas kritis tercatat di histori audit immutable.

---

# 16 Security dan privacy

## 16.01 Application security

- [x] CSRF aktif pada seluruh route web yang mengubah state.
- [x] Authorization seluruh resource melalui policy, role middleware, dan query scope institusi.
- [x] Mass assignment aman melalui atribut `Fillable` dan Form Request validated data.
- [x] Validasi upload file untuk import Peserta Didik, logo, dan backup.
- [x] Batas ukuran upload diterapkan pada request dan service parser.
- [x] Sanitasi nama file melalui generated path, token acak, dan allowlist backup.
- [x] Storage file private untuk dokumen sensitif.
- [x] Signed URL tidak diperlukan pada MVP karena download memakai route Admin terautentikasi dan audit.
- [x] Rate limit login lima percobaan per menit per login/IP.
- [x] Session timeout 120 menit, HttpOnly, SameSite, dan encryption dapat dikonfigurasi per environment.
- [x] Password hashing memakai cast `hashed` Laravel.
- [x] Jangan log password/token; audit hanya menyimpan metadata operasional yang diperlukan.
- [x] Error production tidak membocorkan stack trace atau detail command/database.

## 16.02 Data privacy

- [x] Data siswa tidak tampil di halaman publik.
- [x] Export dibatasi role dan institusi.
- [x] Audit akses data sensitif untuk Peserta Didik dan rapor serta audit export.
- [x] Backup terenkripsi menggunakan APP_KEY ketika `BACKUP_ENCRYPTION=true`.
- [x] Kebijakan penghapusan/nonaktif data mengikuti histori enrollment/rapor.
- [x] Kebijakan retensi rapor dan backup terdokumentasi.
- [x] Dokumen privacy internal dibuat di `PRIVACY-AND-DATA-SECURITY.md`.

## 16.03 Security testing

- [x] Test IDOR student.
- [x] Test IDOR report card.
- [x] Test akses lintas institusi.
- [x] Test upload file.
- [x] Test session logout.
- [x] Test brute force login.
- [x] Test unauthorized export.

**Gate 16:** Lulus. Security headers, session/cookie hardening, policy dan scope lintas institusi, upload validation, private/encrypted backup, audit akses, privacy policy internal, dependency audit, PHPUnit, dan Playwright telah diverifikasi.

---

# 17 Testing dan quality assurance

## 17.01 Unit test

- [ ] Predikat dari nilai.
- [ ] Validasi KKM.
- [ ] Validasi nilai 0–100.
- [ ] Perhitungan rata-rata.
- [ ] Penentuan enrollment aktif.
- [ ] Penentuan sumber rapor transfer.
- [ ] Transisi status rapor.
- [ ] Nama file export.

## 17.02 Feature test

- [ ] Auth.
- [ ] Role.
- [ ] Institusi.
- [ ] Periode.
- [ ] Kelas.
- [ ] Guru.
- [ ] Siswa.
- [ ] Enrollment.
- [ ] Transfer.
- [ ] Mapel.
- [ ] Assignment.
- [ ] Snippet.
- [ ] Input nilai.
- [ ] Kelengkapan rapor.
- [ ] Approve/reject.
- [ ] Lock/unlock.
- [ ] Audit.
- [ ] Export.

## 17.03 Test data matrix

- [ ] 1 siswa, 1 mapel.
- [ ] 1 kelas, 7 mapel.
- [ ] 1 kelas, 20+ mapel.
- [ ] 100 siswa.
- [ ] 500 siswa.
- [ ] Deskripsi kosong.
- [ ] Deskripsi panjang.
- [ ] Nama panjang.
- [ ] Mapel custom.
- [ ] Siswa pindah kelas.
- [ ] Rapor terkunci.
- [ ] Multiple Guru bersamaan.

## 17.04 Browser/manual QA

- [ ] Chrome desktop.
- [ ] Firefox desktop.
- [ ] Browser tablet.
- [ ] Width 1280 px.
- [ ] Width 768 px.
- [ ] Keyboard-only navigation.
- [ ] Error form.
- [ ] Empty state.
- [ ] Loading state.
- [ ] Print preview.

## 17.05 Gate QA

- [ ] Semua test otomatis lulus.
- [ ] Tidak ada error console.
- [ ] Tidak ada error Laravel log.
- [ ] Tidak ada masalah layout kritis.
- [ ] Acceptance test client lulus.

---

# 18 Performance

- [ ] Ukur dashboard tanpa data.
- [ ] Ukur dashboard dengan 12 kelas.
- [ ] Ukur tabel siswa 100 data.
- [ ] Ukur input nilai 100 siswa.
- [ ] Ukur leger 100 siswa dan 20 mapel.
- [ ] Tambahkan index query yang diperlukan.
- [ ] Hindari N+1 pada tabel.
- [ ] Pagination semua daftar besar.
- [ ] Queue export batch bila waktunya melebihi target.
- [ ] Ukur waktu generate PDF satu kelas.
- [ ] Ukur memory export.
- [ ] Target halaman umum: p95 di bawah 3 detik pada environment normal.

---

# 19 Deployment online

## 19.01 Infrastruktur

- [ ] Pilih VPS/hosting.
- [ ] Siapkan PHP sesuai versi.
- [ ] Siapkan database.
- [ ] Siapkan web server.
- [ ] Siapkan SSL.
- [ ] Siapkan domain/subdomain.
- [ ] Siapkan storage.
- [ ] Siapkan queue worker jika diperlukan.
- [ ] Siapkan scheduler.

## 19.02 Release

- [ ] Environment production.
- [ ] APP_KEY.
- [ ] APP_ENV production.
- [ ] APP_DEBUG false.
- [ ] Database credentials.
- [ ] Storage link.
- [ ] Build frontend.
- [ ] Migrate production dengan aman.
- [ ] Seed hanya data aman.
- [ ] Cache config/routes/views.
- [ ] Health check.
- [ ] Error monitoring.

## 19.03 Operasional

- [ ] Backup otomatis.
- [ ] Restore drill.
- [ ] Log rotation.
- [ ] Monitoring uptime.
- [ ] Monitoring disk.
- [ ] Monitoring database.
- [ ] Prosedur rollback.
- [ ] Prosedur update.

---

# 20 Deployment localhost/LAN

- [ ] Installer atau panduan instalasi.
- [ ] PHP runtime lokal.
- [ ] Database lokal.
- [ ] Web server lokal.
- [ ] Konfigurasi `.env` local.
- [ ] Seed awal.
- [ ] Build asset.
- [ ] Akses melalui `127.0.0.1`.
- [ ] Akses melalui IP LAN.
- [ ] Firewall port lokal.
- [ ] Backup ke flashdisk/media eksternal.
- [ ] Restore lokal.
- [ ] Panduan update aplikasi.
- [ ] Panduan reset password Admin.
- [ ] Panduan troubleshooting.
- [ ] Penjelasan bahwa localhost dan online belum sinkron otomatis.

## Gate localhost

- [ ] Aplikasi berjalan tanpa internet.
- [ ] Dua komputer LAN dapat mengakses aplikasi.
- [ ] Database tetap dapat diakses setelah restart.
- [ ] Export PDF/Excel berjalan.
- [ ] Backup dan restore berhasil.

---

# 21 User acceptance test

## Skenario Admin

- [ ] Login sebagai Admin.
- [ ] Membuat institusi.
- [ ] Membuat tahun pelajaran.
- [ ] Membuat semester.
- [ ] Membuat program.
- [ ] Membuat tingkat 1–12.
- [ ] Membuat kelas.
- [ ] Membuat Guru.
- [ ] Menetapkan wali kelas.
- [ ] Membuat mapel custom.
- [ ] Menetapkan KKM.
- [ ] Menetapkan Guru mapel.
- [ ] Membuat siswa.
- [ ] Menempatkan siswa.
- [ ] Memindahkan siswa.
- [ ] Lock rapor.
- [ ] Unlock rapor dengan alasan.
- [ ] Melihat audit.
- [ ] Membuat backup.

## Skenario Guru

- [ ] Login sebagai Guru.
- [ ] Melihat assignment.
- [ ] Membuat snippet.
- [ ] Menggunakan snippet.
- [ ] Mengisi nilai.
- [ ] Mengisi predikat.
- [ ] Mengisi deskripsi.
- [ ] Menyimpan draft.
- [ ] Submit nilai.
- [ ] Memperbaiki nilai setelah reject.
- [ ] Jika wali kelas, mengisi sikap.
- [ ] Jika wali kelas, mengisi absensi.
- [ ] Jika wali kelas, mengisi ekstra.
- [ ] Jika wali kelas, mengisi catatan.
- [ ] Melihat preview.

## Skenario Kepsek

- [ ] Login sebagai Kepsek.
- [ ] Melihat rapor menunggu review.
- [ ] Preview rapor.
- [ ] Approve rapor.
- [ ] Reject rapor dengan alasan.
- [ ] Melihat leger.
- [ ] Tidak dapat unlock.

## Skenario penerbitan

- [ ] Semua mapel lengkap.
- [ ] Komponen rapor lengkap.
- [ ] Kepsek approve.
- [ ] Admin lock.
- [ ] Admin export PDF.
- [ ] Admin export leger Excel.
- [ ] Nilai PDF dan leger konsisten.

---

# 22 Rilis MVP

## 22.01 Release checklist

- [ ] Semua Must-have selesai.
- [ ] Tidak ada blocker.
- [ ] Database migration bersih.
- [ ] Seed demo bersih.
- [ ] Test lulus.
- [ ] Build lulus.
- [ ] Backup teruji.
- [ ] Security checklist lulus.
- [ ] PDF sample disetujui.
- [ ] Leger sample disetujui.
- [ ] UAT disetujui.
- [ ] README diperbarui.
- [ ] Changelog dibuat.
- [ ] Tag release dibuat.

## 22.02 Handover

- [ ] URL aplikasi online.
- [ ] Panduan login.
- [ ] Panduan Admin.
- [ ] Panduan Guru.
- [ ] Panduan Kepsek.
- [ ] Panduan backup.
- [ ] Panduan restore.
- [ ] Panduan localhost.
- [ ] Daftar akun awal diserahkan secara aman.
- [ ] Daftar batasan MVP diserahkan.
- [ ] Daftar pekerjaan pasca-MVP diserahkan.

---

# 23 Pasca-MVP

- [ ] Import massal nilai.
- [ ] Import massal siswa.
- [ ] Portal siswa.
- [ ] Portal orang tua.
- [ ] Distribusi rapor digital.
- [ ] Notifikasi email/WhatsApp.
- [ ] Sinkronisasi online-localhost.
- [ ] Grafik perkembangan nilai.
- [ ] Buku induk.
- [ ] Transkrip.
- [ ] Surat keterangan lulus.
- [ ] Tanda tangan digital.
- [ ] Audit lanjutan.
- [ ] Multi-PKBM/tenant.

---

# Definition of Done tahap besar

## Tahap A Fondasi

- [ ] Project dapat di-install dari README.
- [ ] Database dapat migrate dan seed.
- [ ] Dashboard dapat dibuka.
- [ ] Test baseline lulus.

## Tahap B Autentikasi

- [ ] Admin, Guru, Kepsek dapat login.
- [ ] Permission teruji.
- [ ] Tidak ada route privat bocor.

## Tahap C Master Data

- [ ] Periode, kelas, Guru, Peserta Didik, mapel, assignment, dan KKM lengkap.
- [ ] Import dan validasi selesai.
- [ ] Data dapat digunakan oleh modul penilaian.

## Tahap D Penilaian

- [ ] Guru dapat mengisi nilai dari awal sampai submit.
- [ ] Deskripsi reusable bekerja.
- [ ] Concurrency teruji.

## Tahap E Rapor

- [ ] Kelengkapan rapor lengkap.
- [ ] Approve, lock, unlock, dan audit selesai.
- [ ] PDF sesuai format.

## Tahap F Leger

- [ ] Leger Excel dan PDF selesai.
- [ ] Nilai konsisten dengan rapor.
- [ ] Format disetujui client.

## Tahap G Deployment

- [ ] Online berjalan.
- [ ] Localhost/LAN berjalan.
- [ ] Backup/restore teruji.
- [ ] Handover lengkap.
