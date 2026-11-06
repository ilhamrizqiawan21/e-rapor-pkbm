# Scope dan Glossary E-Rapor PKBM

Status: **Baseline v0.3 — terkunci untuk tahap implementasi MVP**  
Tanggal: 6 November 2026

Dokumen ini adalah sumber kebenaran untuk batas produk, istilah, hak akses, status workflow, dan keputusan bisnis E-Rapor PKBM. Jika PRD, ERD, desain, atau TODO memiliki istilah/aturan yang berbeda, dokumen ini yang dipakai sampai ada keputusan perubahan yang dicatat.

## 1. Definisi produk

E-Rapor PKBM adalah aplikasi web internal untuk mengelola data akademik dan menerbitkan rapor peserta didik PKBM tingkat 1–12. Produk ini berfokus pada penilaian, kelengkapan rapor, pemeriksaan, penguncian, pencetakan rapor, dan leger.

Produk ini bukan ERP sekolah dan bukan portal peserta didik/orang tua.

## 2. Scope MVP yang dikunci

### Termasuk

- Satu instalasi untuk satu PKBM.
- Program Paket A, B, dan C sebagai data program; default mapping tingkat: Paket A = tingkat 1–6, Paket B = tingkat 7–9, Paket C = tingkat 10–12. Mapping tetap dapat disesuaikan bila kebutuhan PKBM berbeda.
- Kelas/rombel tingkat 1–12 dengan struktur format rapor yang sama.
- Master peserta didik, Guru, pengguna, program, tingkat, kelas, mata pelajaran, tahun pelajaran, semester, KKM, dan bank deskripsi.
- Penugasan Guru per kelas dan mata pelajaran serta penugasan wali kelas kepada akun Guru.
- Nilai angka 0–100.
- Predikat otomatis dari rentang predikat yang dapat dikonfigurasi Admin; default skema A/B/C/D akan disiapkan sebagai konfigurasi awal, bukan hard-code.
- KKM per tahun pelajaran, semester, kelas, dan mata pelajaran; nilai KKM disalin sebagai snapshot ke rapor saat data diajukan/disetujui.
- Deskripsi diketik manual, dapat dimulai dari snippet reusable, lalu disimpan sebagai teks final per peserta didik.
- Sikap, absensi, ekstrakurikuler, catatan wali kelas, tanda tangan, dan nomor dokumen manual.
- Perpindahan kelas dengan tanggal efektif, alasan, dan riwayat.
- Satu rapor resmi per peserta didik per semester. Jika pindah kelas di tengah semester, Admin memilih penempatan yang menjadi sumber rapor; sistem tidak menggabungkan dua penempatan secara otomatis.
- Workflow draft, pengajuan, pemeriksaan, perbaikan, persetujuan, dan penguncian.
- Admin sebagai satu-satunya role yang dapat lock/unlock rapor. Unlock wajib memakai alasan dan audit log.
- Preview dan PDF rapor A4 portrait, satuan maupun batch kelas.
- Leger dari sumber nilai yang sama dengan rapor: Excel sebagai output utama dan PDF landscape sebagai output tambahan. Leger tidak menampilkan peringkat pada MVP.
- Import Excel peserta didik bila dibutuhkan operasional. Import nilai tidak termasuk MVP awal.
- Mode localhost/LAN tanpa internet dan mode online HTTPS menggunakan kode serta skema database yang sama. Tidak ada sinkronisasi dua arah antar-mode pada MVP.

### Tidak termasuk MVP

- Sinkronisasi online–localhost.
- Portal peserta didik atau orang tua.
- Keuangan, pembayaran, honor, atau inventaris.
- Presensi harian QR/GPS.
- Integrasi Dapodik/EMIS.
- Deskripsi berbasis AI.
- Tanda tangan elektronik tersertifikasi.
- Aplikasi mobile native.
- Notifikasi WhatsApp/email.
- Jadwal pelajaran, payroll Guru, ranking, analitik lanjutan, buku induk, transkrip, SKL, dan multi-PKBM dalam satu instalasi.

## 3. Istilah canonical

| Istilah resmi di UI/dokumen | Kode/istilah teknis | Makna dan aturan |
|---|---|---|
| Admin | `ADMIN` | Pengelola seluruh data dan satu-satunya pemilik aksi lock/unlock. |
| Guru | `GURU` | Pengguna yang mengisi nilai berdasarkan penugasan. Istilah “tutor” tidak dipakai sebagai label role. |
| Kepala PKBM | `KEPSEK` | Role pemeriksa dan penyetuju. “Kepsek” boleh dipakai sebagai singkatan internal, tetapi label UI memakai “Kepala PKBM” bila ruang memungkinkan. |
| Peserta Didik | `students` | Istilah resmi UI dan dokumen. “Siswa” hanya boleh dipakai dalam percakapan singkat, bukan label utama. |
| Tahun Pelajaran | `academic_years` | Periode seperti 2025/2026. |
| Semester | `semesters` | `GANJIL` atau `GENAP`. |
| Program | `programs` | Paket A, Paket B, Paket C, atau program internal PKBM. |
| Tingkat | `levels` | Angka 1–12 dalam suatu program. |
| Kelas | `academic_classes` | Rombel operasional pada periode tertentu. “Rombel” hanya istilah teknis bantu. |
| Penempatan Peserta Didik | `enrollments` | Relasi peserta didik dengan kelas dan periode. |
| Perpindahan Kelas | `class_transfers` | Perubahan penempatan yang menyimpan asal, tujuan, tanggal efektif, alasan, dan pelaksana. |
| Mata Pelajaran | `subjects` | Master mapel yang dapat ditambah, dinonaktifkan, dan diurutkan. “Mapel” adalah singkatan UI yang boleh dipakai pada ruang sempit. |
| Penugasan Mata Pelajaran | `subject_assignments` | Relasi Guru–kelas–mata pelajaran–semester. |
| Nilai | `score` | Nilai angka 0–100. Istilah “grade” tidak dipakai untuk nilai angka. |
| KKM | `minimum_score` | Nilai minimum konfigurasi pada kombinasi tahun pelajaran, semester, kelas, dan mapel. |
| Predikat | `predicate` | Hasil A/B/C/D dari rentang konfigurasi. Tidak diketik terpisah oleh Guru pada alur normal. |
| Deskripsi | `description` | Teks final per peserta didik dan mata pelajaran. |
| Bank Deskripsi | `description_snippets` | Teks reusable milik Guru atau bersama PKBM. Snippet tidak menggantikan teks final rapor. |
| Rapor | `report_cards` | Dokumen hasil penilaian seorang peserta didik pada satu semester. |
| Leger Nilai | `ledger` | Rekap kelas dari sumber nilai rapor yang telah disetujui/terkunci. |
| Wali Kelas | `homeroom assignment` | Penugasan tambahan pada akun Guru, bukan role aplikasi baru. |
| Kunci Rapor | `LOCKED` | Rapor resmi yang tidak dapat diubah melalui alur normal. |
| Buka Kunci Rapor | `UNLOCK` | Aksi Admin untuk mengembalikan rapor terkunci ke Draft dengan alasan. |

## 4. Role dan kewenangan

| Kemampuan | Admin | Guru | Kepala PKBM |
|---|:---:|:---:|:---:|
| Mengelola pengguna dan master data | Ya | Tidak | Tidak |
| Mengelola periode, kelas, mapel, KKM, dan penugasan | Ya | Tidak | Tidak |
| Mengisi nilai pada penugasan sendiri | Dapat koreksi administratif | Ya | Tidak |
| Mengisi komponen wali kelas pada kelas yang ditugaskan | Dapat koreksi administratif | Ya | Tidak |
| Mengajukan data penilaian | Ya bila diperlukan | Ya | Tidak |
| Memeriksa kelengkapan rapor kelas sendiri | Ya | Ya sebagai wali kelas | Ya secara institusional |
| Menyetujui/meminta perbaikan | Tidak sebagai keputusan Kepala PKBM | Tidak | Ya |
| Lock rapor | Ya | Tidak | Tidak |
| Unlock rapor | Ya, wajib alasan | Tidak | Tidak |
| Melihat/export data institusi | Ya | Sesuai penugasan | Ya |

Satu akun boleh memiliki lebih dari satu role melalui pivot, tetapi konfigurasi normalnya satu role utama. Penugasan wali kelas tidak membuat role tambahan.

## 5. Struktur domain

```text
PKBM (satu instalasi)
└── Tahun Pelajaran
    └── Semester
        └── Program
            └── Tingkat 1–12
                └── Kelas
                    ├── Penempatan Peserta Didik
                    ├── Penugasan Mata Pelajaran
                    └── Rapor Peserta Didik
```

## 6. Workflow rapor

```text
DRAFT → SUBMITTED → IN_REVIEW → APPROVED → LOCKED
  ↑         ↑             │           │
  └─────────┴─────────────┘           │
                                      │ Admin unlock + alasan
                                      └──────────────→ DRAFT
```

Label UI:

- `DRAFT` — Draft
- `SUBMITTED` — Diajukan
- `IN_REVIEW` — Sedang Diperiksa
- `NEEDS_REVISION` — Perlu Perbaikan; kembali ke Draft setelah dikembalikan kepada pengisi.
- `APPROVED` — Disetujui
- `LOCKED` — Terkunci

Alur utama: Guru mapel mengisi dan mengajukan → Guru wali kelas memastikan komponen kelas lengkap → Kepala PKBM menyetujui atau meminta perbaikan → Admin mengunci dan mengarsipkan PDF.

## 7. Aturan bisnis yang tidak boleh ambigu

1. Nilai selalu divalidasi pada rentang 0–100.
2. Predikat dihitung dari konfigurasi rentang aktif; rentang dapat diubah Admin untuk kebutuhan PKBM dan hasil predikat disimpan sebagai snapshot rapor.
3. KKM dapat berbeda untuk setiap tahun pelajaran, semester, kelas, dan mata pelajaran.
4. Deskripsi wajib diisi sebelum pengajuan resmi; snippet hanya alat bantu dan teks yang tersimpan di rapor adalah salinan final yang dapat diedit.
5. Satu peserta didik tidak boleh memiliki dua rapor resmi untuk semester yang sama.
6. Saat pindah kelas di tengah semester, Admin memilih enrollment pelaporan. Nilai tidak digabung otomatis dari dua kelas.
7. Nomor dokumen diisi Guru wali kelas pada Draft dan dapat dikoreksi Admin sebelum lock. Nomor menjadi snapshot ketika rapor dikunci.
8. Tanda tangan pada MVP menggunakan nama/jabatan dan aset gambar tanda tangan bila tersedia; tanda tangan elektronik tersertifikasi berada di luar scope.
9. Rapor memakai A4 portrait. Leger memakai Excel sebagai output utama dan PDF landscape sebagai output tambahan.
10. Data leger harus berasal dari data nilai rapor yang sama, bukan tabel nilai duplikat.
11. Ledger resmi hanya dapat diekspor dari rapor yang telah `APPROVED` atau `LOCKED`.
12. Data terkunci tidak boleh diedit langsung; perubahan hanya melalui unlock Admin yang diaudit.

### Contoh perpindahan tengah semester

Peserta Didik A berada di Kelas 3A sampai 15 September, lalu dipindahkan Admin ke Kelas 3B mulai 16 September. Saat semester Ganjil ditutup, Admin memilih enrollment Kelas 3B sebagai **penempatan pelaporan**. Sistem membuat satu Rapor Ganjil untuk Kelas 3B; nilai dari Kelas 3A tidak digabung otomatis. Riwayat Kelas 3A tetap tersimpan dan dapat dilihat pada timeline perpindahan.

## 8. Keputusan yang sengaja ditunda

Keputusan berikut tidak menghambat implementasi MVP dan akan dicatat sebagai ADR bila dibutuhkan:

- Detail visual final logo, cap, dan posisi tanda tangan sesuai identitas PKBM.
- Apakah setiap instalasi memerlukan SMTP atau cukup log lokal pada deployment localhost.
- Format backup otomatis dan retensi arsip per lingkungan.
- Portal peserta didik/orang tua, integrasi eksternal, dan sinkronisasi antar-mode.
- Kebutuhan tambahan seperti ranking, buku induk, transkrip, atau SKL.

## 9. Acceptance gate scope

Scope ini dianggap selesai dikunci apabila:

- Semua istilah role, peserta didik, tingkat, kelas, nilai, KKM, predikat, rapor, leger, lock, dan unlock konsisten pada PRD/ERD/desain/TODO.
- Tidak ada pertanyaan produk P0 yang tersisa sebelum modul autentikasi dimulai.
- Contoh perpindahan kelas tengah semester memiliki satu hasil rapor yang jelas.
- Hak akses Admin, Guru, dan Kepala PKBM tertulis dan dapat diturunkan menjadi policy/test.
- Status workflow dan aksi tiap role dapat diturunkan menjadi state machine.
- Batas MVP dan backlog pasca-MVP dipisahkan.
