# Kebijakan internal privacy dan keamanan data

Dokumen ini adalah baseline operasional E-Rapor PKBM untuk deployment localhost/LAN dan online. Ia wajib disesuaikan dengan kebijakan PKBM dan kewajiban hukum yang berlaku.

## Klasifikasi data

- **Publik terbatas:** nama PKBM, logo, alamat, dan informasi kontak yang dipilih untuk tampil pada dokumen rapor.
- **Internal:** akun pengguna, role, konfigurasi mapel, kelas, tahun pelajaran, dan histori audit.
- **Sensitif:** NIPD/NISN, identitas Peserta Didik, tanggal/tempat lahir, alamat, nama wali, nilai, absensi, sikap, catatan wali kelas, rapor, dan backup database.

Data sensitif hanya boleh diakses melalui session terautentikasi, policy institusi, dan role yang sesuai. Tidak ada halaman publik yang menampilkan data Peserta Didik.

## Prinsip akses

- Admin mengelola data dalam satu institusi dan menjadi satu-satunya role untuk lock/unlock, backup, restore, dan pengaturan akun.
- Guru hanya melihat Peserta Didik, kelas, penugasan, dan rapor yang terkait dengan penugasannya.
- Kepala PKBM hanya melihat data institusinya dan melakukan review/approval sesuai workflow.
- Semua endpoint yang mengubah state memakai CSRF Laravel, Form Request, policy, dan validasi server-side.
- Audit immutable merekam akses dan perubahan kritis tanpa menyimpan password, token, atau isi rahasia kredensial.

## Penyimpanan dan transfer

- Dokumen rapor, preview import, dump database, dan file kerja sensitif menggunakan storage private.
- Backup database terenkripsi menggunakan `APP_KEY` melalui `BACKUP_ENCRYPTION=true`; APP_KEY tidak boleh disimpan di repository atau dikirim lewat chat.
- Production/staging wajib HTTPS, cookie session Secure/HttpOnly/SameSite, session encryption, dan HSTS.
- Export hanya tersedia melalui route terautentikasi dan dicatat pada histori audit.

## Retensi dan penghapusan

- Backup mengikuti `BACKUP_RETENTION_DAYS`; safety backup mengikuti proses retensi yang sama.
- Preview import otomatis kedaluwarsa setelah 30 menit dan terikat pada user serta institusi pembuatnya.
- Peserta Didik yang memiliki histori enrollment/rapor dinonaktifkan, bukan dihapus permanen.
- Audit tidak boleh diubah atau dihapus dari UI; penghapusan administratif hanya boleh melalui prosedur database yang terdokumentasi dan berwenang.
- Rapor yang sudah dikunci diperlakukan sebagai arsip resmi; perubahan hanya melalui unlock Admin dengan alasan.

## Respons insiden

1. Nonaktifkan akun atau rotate credential yang dicurigai.
2. Simpan waktu, actor, endpoint, dan bukti audit tanpa menyalin password/token.
3. Isolasi backup atau export yang terdampak.
4. Pulihkan dari backup tervalidasi pada staging lebih dahulu.
5. Dokumentasikan dampak, tindakan koreksi, dan persetujuan Kepala PKBM/Admin.

## Checklist deployment

- `APP_DEBUG=false`.
- `APP_KEY` unik dan hanya tersedia pada secret manager/environment server.
- `DB_PASSWORD`, SMTP credential, dan cloud storage credential tidak masuk Git.
- `BACKUP_ENCRYPTION=true`, disk backup private, dan scheduler aktif satu kali.
- Log aplikasi tidak berisi password, reset token, APP_KEY, atau isi backup.
- Backup dan restore diuji pada staging sebelum perubahan production.
