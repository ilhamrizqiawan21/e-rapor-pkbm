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

- [ ] Halaman login.
- [ ] Validasi username/email wajib.
- [ ] Validasi password wajib.
- [ ] Pesan error login gagal.
- [ ] Logout.
- [ ] Redirect setelah login berdasarkan role.
- [ ] Redirect user yang belum login.
- [ ] Redirect user yang tidak memiliki akses.
- [ ] Nonaktifkan login user berstatus inactive.
- [ ] Rate limit login.
- [ ] Session regeneration setelah login.
- [ ] Logout invalidates session.
- [ ] Password reset minimal untuk Admin.
- [ ] Ubah password sendiri.

## 02.03 Authorization

- [ ] Middleware authenticated.
- [ ] Middleware role.
- [ ] Policy Institution.
- [ ] Policy AcademicClass.
- [ ] Policy Student.
- [ ] Policy SubjectAssignment.
- [ ] Policy ReportCard.
- [ ] Admin dapat semua data institusinya.
- [ ] Guru hanya dapat melihat penugasan sendiri.
- [ ] Kepsek hanya dapat melihat data institusinya dan melakukan review.
- [ ] Guru wali kelas dapat mengakses kelengkapan kelas yang ditugaskan.
- [ ] Guru tidak dapat lock/unlock.
- [ ] Kepsek tidak dapat unlock.
- [ ] Admin dapat lock/unlock.

## 02.04 UI auth

- [ ] Layout login.
- [ ] State loading login.
- [ ] State error login.
- [ ] Toggle tampil/sembunyikan password.
- [ ] Link lupa password.
- [ ] Menu profile.
- [ ] Menu ubah password.
- [ ] Menu logout.
- [ ] Tampilan role aktif.

## 02.05 Tests

- [ ] Guest tidak dapat membuka dashboard.
- [ ] Login valid berhasil.
- [ ] Login invalid gagal.
- [ ] User inactive ditolak.
- [ ] Admin dapat membuka route Admin.
- [ ] Guru ditolak dari route Admin.
- [ ] Kepsek dapat membuka route review.
- [ ] Guru tidak dapat lock report card.
- [ ] Kepsek tidak dapat unlock report card.
- [ ] Logout menghapus session.

## 02.06 Gate autentikasi

- [ ] Tiga akun role dapat login.
- [ ] Setiap role melihat menu yang tepat.
- [ ] Semua route privat terlindungi.
- [ ] Test auth dan authorization lulus.
- [ ] Tidak ada akses silang antar institusi.

---

# 03 Institusi dan pengaturan PKBM

## 03.01 Institusi

- [ ] CRUD identitas PKBM.
- [ ] Nama PKBM.
- [ ] Alamat.
- [ ] Nomor telepon.
- [ ] Email.
- [ ] Logo.
- [ ] NPSN/nomor izin bila diperlukan.
- [ ] Kode pos.
- [ ] Validasi format email.
- [ ] Validasi ukuran dan tipe logo.
- [ ] Preview logo.
- [ ] Hapus/ganti logo.

## 03.02 Penandatangan

- [ ] Data Kepsek.
- [ ] Nama tampil pada rapor.
- [ ] Jabatan tampil pada rapor.
- [ ] NIP/NIY jika dipakai.
- [ ] Guru wali kelas diambil dari user Guru.
- [ ] Nama orang tua/wali diambil dari data siswa atau input rapor.
- [ ] Snapshot penandatangan saat rapor dikunci.

## 03.03 Pengaturan format

- [ ] Format nama siswa.
- [ ] Format nomor dokumen.
- [ ] Format tanggal Indonesia.
- [ ] Setting tanda tangan.
- [ ] Setting logo pada rapor.
- [ ] Setting margin PDF.
- [ ] Setting header/footer.
- [ ] Setting tampil/tidaknya KKM.
- [ ] Setting tampil/tidaknya predikat.

## 03.04 Gate institusi

- [ ] Admin dapat menyimpan identitas PKBM.
- [ ] Identitas muncul pada preview rapor.
- [ ] Logo tampil tanpa merusak layout.
- [ ] Perubahan institusi tercatat bila audit diaktifkan.

---

# 04 Periode akademik, program, tingkat, dan kelas

## 04.01 Tahun pelajaran

- [ ] CRUD tahun pelajaran.
- [ ] Field nama, tanggal mulai, tanggal selesai.
- [ ] Status draft/aktif/ditutup.
- [ ] Hanya satu tahun pelajaran aktif per institusi.
- [ ] Tidak dapat menghapus periode yang memiliki data.
- [ ] Konfirmasi penutupan periode.

## 04.02 Semester

- [ ] CRUD semester.
- [ ] Semester Ganjil.
- [ ] Semester Genap.
- [ ] Urutan semester.
- [ ] Status draft/aktif/ditutup.
- [ ] Hanya semester aktif yang dapat menerima input nilai.

## 04.03 Program dan tingkat

- [ ] CRUD program.
- [ ] Seed Paket A.
- [ ] Seed Paket B.
- [ ] Seed Paket C.
- [ ] CRUD tingkat 1–12.
- [ ] Validasi tingkat antara 1 dan 12.
- [ ] Mapping tingkat ke program.
- [ ] Opsi nama tingkat custom.

## 04.04 Kelas/rombel

- [ ] CRUD kelas.
- [ ] Kode kelas.
- [ ] Nama kelas.
- [ ] Tahun pelajaran.
- [ ] Program.
- [ ] Tingkat.
- [ ] Guru wali kelas.
- [ ] Status aktif/nonaktif.
- [ ] Daftar siswa per kelas.
- [ ] Daftar mapel per kelas.
- [ ] Progress kelengkapan rapor kelas.
- [ ] Tidak boleh membuat kode kelas duplikat pada periode sama.

## 04.05 Gate periode

- [ ] Admin dapat membuat periode dan semester.
- [ ] Admin dapat membuat kelas 1–12.
- [ ] Guru hanya melihat periode/kelas yang ditugaskan.
- [ ] Periode tertutup menolak perubahan akademik biasa.
- [ ] Test uniqueness periode dan kelas lulus.

---

# 05 Master Guru dan penugasan

## 05.01 Data Guru

- [ ] CRUD user Guru.
- [ ] Nama lengkap.
- [ ] Email/username.
- [ ] NIP/NIY/nomor identitas.
- [ ] Nomor telepon.
- [ ] Status aktif/nonaktif.
- [ ] Reset password oleh Admin.
- [ ] Aktivasi/nonaktivasi akun.
- [ ] Filter Guru aktif/nonaktif.

## 05.02 Wali kelas

- [ ] Pilih Guru wali kelas pada kelas.
- [ ] Satu kelas memiliki maksimal satu wali kelas aktif.
- [ ] Satu Guru dapat menjadi wali beberapa kelas jika diizinkan.
- [ ] Riwayat wali kelas disimpan bila berubah.
- [ ] Guru wali kelas dapat membuka kelengkapan rapor kelas.

## 05.03 Penugasan mapel

- [ ] Assign Guru ke mapel pada kelas.
- [ ] Satu mapel pada satu kelas memiliki Guru utama.
- [ ] Validasi Guru aktif.
- [ ] Validasi mapel aktif.
- [ ] KKM per assignment.
- [ ] Urutan mapel per assignment.
- [ ] Aktivasi/nonaktivasi assignment.
- [ ] Tampilan daftar assignment Guru.
- [ ] Tampilan daftar assignment kelas.

## 05.04 Gate Guru

- [ ] Admin dapat membuat akun Guru.
- [ ] Admin dapat menetapkan wali kelas.
- [ ] Admin dapat menetapkan Guru mapel.
- [ ] Guru hanya melihat assignment sendiri.
- [ ] Perubahan assignment tidak merusak nilai terkunci.

---

# 06 Master peserta didik dan penempatan

## 06.01 Data peserta didik

- [ ] CRUD siswa.
- [ ] NIPD/student number.
- [ ] NISN/national student number.
- [ ] Nama lengkap.
- [ ] Jenis kelamin.
- [ ] Tempat lahir.
- [ ] Tanggal lahir.
- [ ] Alamat.
- [ ] Nama orang tua/wali.
- [ ] Status aktif/nonaktif/lulus/pindah.
- [ ] Search berdasarkan nama, NIPD, NISN.
- [ ] Filter status.
- [ ] Detail siswa.
- [ ] Edit siswa.
- [ ] Nonaktifkan siswa tanpa menghapus histori.

## 06.02 Import siswa

- [ ] Template Excel siswa.
- [ ] Download template.
- [ ] Upload file.
- [ ] Validasi header.
- [ ] Validasi baris kosong.
- [ ] Validasi NIPD duplikat.
- [ ] Validasi tanggal lahir.
- [ ] Preview data sebelum import.
- [ ] Ringkasan sukses/gagal per baris.
- [ ] Import hanya membuat data valid.
- [ ] Download error report.

## 06.03 Enrollment awal

- [ ] Tambahkan siswa ke kelas.
- [ ] Tanggal mulai.
- [ ] Status active.
- [ ] Satu siswa tidak boleh memiliki dua penempatan aktif bersamaan.
- [ ] Daftar siswa kelas.
- [ ] Keluarkan siswa dari kelas dengan alasan.

## 06.04 Perpindahan kelas

- [ ] Form kelas asal.
- [ ] Form kelas tujuan.
- [ ] Tanggal efektif.
- [ ] Alasan.
- [ ] Catatan.
- [ ] Admin sebagai approval.
- [ ] Tutup enrollment lama.
- [ ] Buat enrollment baru.
- [ ] Buat class transfer.
- [ ] Timeline histori siswa.
- [ ] Filter histori berdasarkan tahun pelajaran.
- [ ] Pilihan sumber rapor untuk transfer tengah semester.
- [ ] Audit transfer.
- [ ] Tidak ada hard delete enrollment lama.

## 06.05 Gate siswa

- [ ] CRUD siswa lulus.
- [ ] Import Excel lulus dengan data valid dan invalid.
- [ ] Siswa dapat ditempatkan ke kelas.
- [ ] Siswa dapat dipindahkan tanpa kehilangan histori.
- [ ] Test benturan enrollment lulus.
- [ ] Test transfer tengah semester lulus.

---

# 07 Master mata pelajaran dan KKM

## 07.01 Mata pelajaran

- [ ] CRUD mapel.
- [ ] Kode mapel.
- [ ] Nama mapel.
- [ ] Nama singkat mapel.
- [ ] Kategori mapel.
- [ ] Urutan default.
- [ ] Status aktif/nonaktif.
- [ ] Validasi kode unik per institusi.
- [ ] Validasi nama singkat wajib untuk leger.
- [ ] Preview header leger.

## 07.02 Mapping mapel

- [ ] Mapping mapel ke kelas.
- [ ] Tambah mapel dari kelas.
- [ ] Hapus mapel dari kelas tanpa menghapus master.
- [ ] Atur urutan mapel.
- [ ] Atur Guru pengampu.
- [ ] Atur KKM.
- [ ] Salin konfigurasi mapel dari kelas lain.
- [ ] Bulk add mapel ke beberapa kelas.
- [ ] Bulk assign Guru.

## 07.03 KKM dan predikat

- [ ] Input KKM 0–100.
- [ ] Validasi KKM 0–100.
- [ ] Simpan KKM sebagai assignment.
- [ ] Konfigurasi rentang predikat.
- [ ] Validasi rentang tidak overlap.
- [ ] Preview konversi nilai ke predikat.
- [ ] Snapshot KKM saat report grade dibuat.
- [ ] Snapshot predikat saat report grade dibuat.

## 07.04 Gate mapel

- [ ] Mapel dapat ditambah.
- [ ] Mapel dapat dikurangi dari kelas.
- [ ] Mapel dapat diurutkan.
- [ ] KKM tampil pada input nilai.
- [ ] Nama singkat tampil pada leger.
- [ ] Perubahan master tidak mengubah rapor terkunci.

---

# 08 Bank deskripsi reusable

## 08.01 Data snippet

- [ ] CRUD snippet.
- [ ] Judul snippet.
- [ ] Isi snippet.
- [ ] Mapping mapel opsional.
- [ ] Owner Guru opsional.
- [ ] Visibility private/shared.
- [ ] Status aktif/nonaktif.
- [ ] Search judul dan isi.
- [ ] Filter mapel.
- [ ] Filter milik saya/dibagikan.

## 08.02 Pemakaian snippet

- [ ] Tombol buka bank deskripsi pada input nilai.
- [ ] Preview snippet.
- [ ] Insert snippet ke deskripsi.
- [ ] Deskripsi siswa dapat diedit setelah insert.
- [ ] Perubahan deskripsi siswa tidak mengubah snippet asli.
- [ ] Simpan deskripsi siswa sebagai snippet baru.
- [ ] Permission snippet private.
- [ ] Permission snippet shared.

## 08.03 Gate deskripsi

- [ ] Guru dapat membuat snippet private.
- [ ] Admin dapat membuat snippet shared.
- [ ] Guru dapat menggunakan snippet shared.
- [ ] Guru tidak dapat mengubah snippet milik Guru lain tanpa izin.
- [ ] Nilai rapor menyimpan teks final, bukan hanya referensi snippet.

---

# 09 Input nilai dan penilaian

## 09.01 Halaman input nilai

- [ ] Pilih tahun pelajaran.
- [ ] Pilih semester.
- [ ] Pilih kelas.
- [ ] Pilih mapel.
- [ ] Tampilkan Guru pengampu.
- [ ] Tampilkan KKM.
- [ ] Tampilkan status input.
- [ ] Search siswa.
- [ ] Pagination atau virtual table bila diperlukan.
- [ ] Kolom nilai.
- [ ] Kolom predikat.
- [ ] Kolom deskripsi.
- [ ] Tombol simpan.
- [ ] Tombol simpan semua.
- [ ] Tombol ajukan.
- [ ] Indikator perubahan belum disimpan.

## 09.02 Validasi nilai

- [ ] Nilai wajib jika mapel wajib.
- [ ] Nilai numeric.
- [ ] Nilai minimal 0.
- [ ] Nilai maksimal 100.
- [ ] KKM tampil sebagai referensi.
- [ ] Predikat sesuai konfigurasi.
- [ ] Deskripsi wajib jika kebijakan mewajibkan.
- [ ] Error ditampilkan per siswa.
- [ ] Error tidak menghapus input baris lain.
- [ ] Duplicate grade ditolak.

## 09.03 Concurrency

- [ ] Dua Guru dapat mengisi mapel berbeda pada kelas sama.
- [ ] Update Guru A tidak menimpa mapel Guru B.
- [ ] Timestamp updated_at terlihat saat konflik.
- [ ] Warning jika data berubah setelah halaman dibuka.
- [ ] Strategi optimistic locking atau last-write warning ditetapkan.

## 09.04 Submit workflow

- [ ] Draft.
- [ ] Submit oleh Guru.
- [ ] Review oleh Guru wali kelas/Kepsek.
- [ ] Reject dengan alasan.
- [ ] Revisi kembali ke Guru.
- [ ] Approve oleh Kepsek.
- [ ] Lock oleh Admin.

## 09.05 Gate nilai

- [ ] Guru dapat mengisi satu mapel end-to-end.
- [ ] Guru tidak dapat mengisi mapel yang bukan assignment.
- [ ] Nilai invalid ditolak.
- [ ] Deskripsi reusable bekerja.
- [ ] Submit/reject/approve bekerja.
- [ ] Test concurrency minimal dua assignment lulus.

---

# 10 Kelengkapan rapor

## 10.01 Identitas dan nomor dokumen

- [ ] Tampilkan identitas siswa.
- [ ] Tampilkan NIPD/NISN.
- [ ] Tampilkan kelas.
- [ ] Tampilkan semester.
- [ ] Tampilkan tahun pelajaran.
- [ ] Tampilkan identitas PKBM.
- [ ] Input nomor dokumen manual.
- [ ] Validasi nomor dokumen sesuai kebijakan.
- [ ] Snapshot identitas saat lock.

## 10.02 Sikap

- [ ] Input sikap spiritual.
- [ ] Input sikap sosial.
- [ ] Deskripsi manual.
- [ ] Bank deskripsi dapat dipakai.
- [ ] Validasi komponen wajib.
- [ ] Permission Guru wali kelas.

## 10.03 Absensi

- [ ] Input sakit.
- [ ] Input izin.
- [ ] Input tanpa keterangan.
- [ ] Nilai integer tidak negatif.
- [ ] Tampilkan satuan hari.
- [ ] Validasi angka besar yang tidak wajar.

## 10.04 Ekstrakurikuler

- [ ] Tambah baris kegiatan.
- [ ] Nama kegiatan.
- [ ] Keterangan.
- [ ] Urutkan baris.
- [ ] Hapus baris kosong.
- [ ] Empty state jika tidak ada kegiatan.

## 10.05 Catatan wali kelas

- [ ] Input catatan/saran.
- [ ] Bank deskripsi opsional.
- [ ] Counter karakter.
- [ ] Preview catatan pada rapor.

## 10.06 Gate kelengkapan

- [ ] Progress per siswa.
- [ ] Progress per kelas.
- [ ] Komponen kosong teridentifikasi.
- [ ] Sistem menolak approve jika wajib kosong.
- [ ] Guru wali kelas dapat memperbaiki data.

---

# 11 Workflow approve, lock, unlock, audit

## 11.01 State machine

- [ ] DRAFT.
- [ ] SUBMITTED.
- [ ] IN_REVIEW.
- [ ] NEEDS_REVISION.
- [ ] APPROVED.
- [ ] LOCKED.
- [ ] Unlock kembali ke DRAFT.
- [ ] Semua transisi memiliki actor.
- [ ] Semua transisi memiliki timestamp.
- [ ] Reject memiliki alasan.
- [ ] Unlock memiliki alasan.

## 11.02 Permission state

- [ ] Guru dapat edit DRAFT miliknya.
- [ ] Guru dapat submit.
- [ ] Guru wali kelas dapat review kelengkapan.
- [ ] Kepsek dapat approve/reject.
- [ ] Admin dapat lock.
- [ ] Admin dapat unlock.
- [ ] Data LOCKED tidak dapat diedit oleh Guru.
- [ ] Data LOCKED tidak dapat diedit oleh Kepsek.
- [ ] Admin unlock harus melalui modal alasan.

## 11.03 Audit log

- [ ] CREATE.
- [ ] UPDATE.
- [ ] SUBMIT.
- [ ] REVIEW.
- [ ] APPROVE.
- [ ] REJECT.
- [ ] LOCK.
- [ ] UNLOCK.
- [ ] TRANSFER.
- [ ] Simpan actor.
- [ ] Simpan entity.
- [ ] Simpan nilai lama.
- [ ] Simpan nilai baru.
- [ ] Simpan alasan.
- [ ] Halaman lihat histori.
- [ ] Filter berdasarkan actor, action, tanggal, entity.

## 11.04 Gate workflow

- [ ] Tidak ada transisi ilegal.
- [ ] Unlock selalu tercatat.
- [ ] Audit tidak dapat diubah melalui UI biasa.
- [ ] Test seluruh transisi lulus.

---

# 12 Generator rapor PDF

## 12.01 Template

- [ ] Pilih engine PDF.
- [ ] Buat template halaman identitas.
- [ ] Buat tabel capaian kompetensi.
- [ ] Buat tabel sikap.
- [ ] Buat tabel ekstrakurikuler.
- [ ] Buat tabel absensi.
- [ ] Buat catatan/saran.
- [ ] Buat area tanda tangan.
- [ ] Buat nomor dokumen.
- [ ] Buat header/footer.
- [ ] Buat halaman nomor jika diperlukan.

## 12.02 Layout dan overflow

- [ ] Deskripsi satu baris.
- [ ] Deskripsi multi-baris.
- [ ] Deskripsi sangat panjang.
- [ ] Mapel sedikit.
- [ ] Mapel banyak.
- [ ] Ekstrakurikuler kosong.
- [ ] Ekstrakurikuler banyak.
- [ ] Catatan kosong.
- [ ] Catatan panjang.
- [ ] Nama siswa panjang.
- [ ] Nama PKBM panjang.
- [ ] Logo besar/kecil.
- [ ] Halaman terpisah tidak memotong tabel.

## 12.03 Preview dan export

- [ ] Preview browser.
- [ ] Download satu rapor.
- [ ] Cetak satu rapor.
- [ ] Generate batch satu kelas.
- [ ] Nama file konsisten.
- [ ] PDF tidak dapat dibuat dari rapor belum lengkap.
- [ ] PDF mengambil snapshot rapor terkunci.
- [ ] Error export tampil ramah.

## 12.04 Gate PDF

- [ ] Hasil PDF dibandingkan dengan DOCX referensi.
- [ ] Tidak ada clipping.
- [ ] Tidak ada overlap.
- [ ] Tidak ada tabel keluar halaman.
- [ ] Semua tanda tangan berada pada posisi benar.
- [ ] PDF dapat dibuka pada browser dan PDF reader.

---

# 13 Generator leger Excel dan PDF

## 13.01 Format leger utama

- [ ] Header PKBM.
- [ ] Program.
- [ ] Kelas.
- [ ] Semester.
- [ ] Tahun pelajaran.
- [ ] Guru wali kelas.
- [ ] No.
- [ ] NIPD/NISN.
- [ ] Nama Peserta Didik.
- [ ] Kolom nilai per mapel memakai nama singkat.
- [ ] Rata-rata per Peserta Didik.
- [ ] Sakit.
- [ ] Izin.
- [ ] Tanpa keterangan.
- [ ] Keterangan.
- [ ] Footer tanggal/cetak.
- [ ] Footer tanda tangan bila disepakati.

## 13.02 Sheet detail

- [ ] Detail mapel.
- [ ] KKM.
- [ ] Nilai.
- [ ] Predikat.
- [ ] Status nilai.
- [ ] Guru pengampu.
- [ ] Deskripsi opsional.
- [ ] Metadata periode.

## 13.03 Excel

- [ ] Export satu kelas.
- [ ] Export semua kelas sesuai permission.
- [ ] Nama file.
- [ ] Nama sheet aman dari karakter invalid.
- [ ] Header freeze.
- [ ] Auto width.
- [ ] Number format nilai.
- [ ] Border dan alignment.
- [ ] Row average.
- [ ] Summary sheet.
- [ ] Data kosong ditandai.

## 13.04 PDF leger

- [ ] Landscape.
- [ ] Repeat header.
- [ ] Tabel banyak mapel.
- [ ] Page break aman.
- [ ] Font terbaca.
- [ ] Export satu kelas.
- [ ] Export seluruh kelas.

## 13.05 Gate leger

- [ ] Nilai leger sama dengan nilai rapor.
- [ ] Nilai kosong terdeteksi.
- [ ] Mapel memakai nama singkat.
- [ ] KKM/predikat tersedia sesuai keputusan.
- [ ] Export Excel bisa dibuka.
- [ ] Export PDF bisa dibuka.

---

# 14 Dashboard dan monitoring

## 14.01 Dashboard Admin

- [ ] Jumlah kelas.
- [ ] Jumlah siswa.
- [ ] Jumlah Guru.
- [ ] Periode aktif.
- [ ] Progress nilai per kelas.
- [ ] Rapor menunggu review.
- [ ] Rapor siap lock.
- [ ] Rapor terkunci.
- [ ] Transfer kelas terbaru.
- [ ] Link tindakan cepat.

## 14.02 Dashboard Guru

- [ ] Assignment mapel.
- [ ] Progress input per mapel.
- [ ] Draft belum submit.
- [ ] Revisi dari review.
- [ ] Kelas wali.
- [ ] Shortcut bank deskripsi.

## 14.03 Dashboard Kepsek

- [ ] Rapor menunggu review.
- [ ] Rapor disetujui.
- [ ] Rapor ditolak.
- [ ] Ringkasan per kelas.
- [ ] Link preview.
- [ ] Link approve/reject.

## 14.04 Gate dashboard

- [ ] Semua angka berasal dari query aktual.
- [ ] Dashboard menghormati permission.
- [ ] Empty state tersedia.
- [ ] Dashboard tidak gagal jika belum ada periode.

---

# 15 Backup, restore, dan audit operasional

## 15.01 Backup

- [ ] Tentukan format backup database.
- [ ] Backup manual oleh Admin.
- [ ] Backup otomatis online.
- [ ] Nama file backup konsisten.
- [ ] Timestamp backup.
- [ ] Penyimpanan backup aman.
- [ ] Retensi backup.
- [ ] Notifikasi backup berhasil/gagal.

## 15.02 Restore

- [ ] Restore di environment staging.
- [ ] Validasi file backup.
- [ ] Konfirmasi destructive restore.
- [ ] Log actor restore.
- [ ] Smoke test setelah restore.
- [ ] Dokumentasikan prosedur restore.

## 15.03 Audit

- [ ] Audit login.
- [ ] Audit perubahan role.
- [ ] Audit perubahan siswa.
- [ ] Audit transfer kelas.
- [ ] Audit perubahan nilai.
- [ ] Audit lock/unlock.
- [ ] Audit export.
- [ ] Filter dan pagination audit.

## 15.04 Gate operasional

- [ ] Backup dapat dibuat.
- [ ] Backup dapat dipulihkan di staging.
- [ ] Data setelah restore konsisten.
- [ ] Audit mencatat aktivitas kritis.

---

# 16 Security dan privacy

## 16.01 Application security

- [ ] CSRF aktif.
- [ ] Authorization seluruh resource.
- [ ] Mass assignment aman.
- [ ] Validasi upload file.
- [ ] Batas ukuran upload.
- [ ] Sanitasi nama file.
- [ ] Storage file private untuk dokumen sensitif.
- [ ] Signed URL untuk download bila diperlukan.
- [ ] Rate limit login.
- [ ] Session timeout.
- [ ] Password hashing.
- [ ] Jangan log password/token.
- [ ] Error production tidak membocorkan stack trace.

## 16.02 Data privacy

- [ ] Data siswa tidak tampil di halaman publik.
- [ ] Export dibatasi role.
- [ ] Audit akses data sensitif.
- [ ] Backup terenkripsi jika memungkinkan.
- [ ] Kebijakan penghapusan/nonaktif data.
- [ ] Kebijakan retensi rapor.
- [ ] Dokumen privacy internal.

## 16.03 Security testing

- [ ] Test IDOR student.
- [ ] Test IDOR report card.
- [ ] Test akses lintas institusi.
- [ ] Test upload file.
- [ ] Test session logout.
- [ ] Test brute force login.
- [ ] Test unauthorized export.

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
