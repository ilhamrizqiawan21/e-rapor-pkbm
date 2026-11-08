<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Buku Induk - {{ $student->full_name }} ({{ $student->student_number }})</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 12mm 12mm; }
        * { box-sizing: border-box; }
        body { color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; margin: 0; line-height: 1.35; }
        .page { page-break-after: always; width: 100%; }
        .page:last-child { page-break-after: auto; }
        .kop { border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 12px; text-align: center; position: relative; }
        .kop-logo { position: absolute; left: 0; top: 0; max-height: 55px; max-width: 75px; }
        .kop h1 { font-size: 13pt; margin: 0 0 2px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .kop h2 { font-size: 11pt; margin: 0 0 2px; text-transform: uppercase; font-weight: 700; color: #1e3a8a; }
        .kop p { font-size: 7.5pt; margin: 0; color: #374151; }
        .title { text-align: center; margin: 10px 0 14px; }
        .title h3 { font-size: 11pt; margin: 0; text-transform: uppercase; font-weight: 700; text-decoration: underline; }
        .title p { font-size: 8pt; margin: 2px 0 0; color: #4b5563; }
        
        .section-header { background: #f1f5f9; border-left: 4px solid #1e3a8a; padding: 3px 6px; font-weight: 700; font-size: 8.5pt; text-transform: uppercase; margin: 10px 0 6px; }
        
        table.grid { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.grid td { padding: 3px 4px; vertical-align: top; }
        table.grid .label { width: 33%; color: #374151; }
        table.grid .separator { width: 2%; text-align: center; }
        table.grid .value { width: 65%; font-weight: 600; color: #000; }

        /* Container 2 Kolom: Kolom Kiri Keterangan Diri Siswa & Kolom Kanan Pas Foto */
        .profile-two-col { display: table; width: 100%; table-layout: fixed; margin-bottom: 6px; }
        .profile-col-left { display: table-cell; width: 77%; vertical-align: top; padding-right: 20px; }
        .profile-col-right { display: table-cell; width: 23%; vertical-align: top; text-align: center; padding-left: 6px; }
        .photo-wrapper { display: inline-block; padding-top: 26px; }
        .photo-table { width: 30mm; height: 40mm; border: 1px dashed #64748b; background: #f8fafc; border-collapse: collapse; margin: 0 auto; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
        .photo-cell { text-align: center; vertical-align: middle; font-size: 8pt; font-weight: 700; color: #64748b; letter-spacing: 0.5px; line-height: 1.5; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 10px; font-size: 8pt; }
        table.data-table th, table.data-table td { border: 1px solid #4b5563; padding: 4px 5px; }
        table.data-table th { background: #e2e8f0; font-weight: 700; text-align: center; }
        table.data-table td.center { text-align: center; }
        table.data-table td.right { text-align: right; }

        .signatures { display: table; width: 100%; margin-top: 18px; page-break-inside: avoid; }
        .signature-col { display: table-cell; width: 50%; vertical-align: top; }
        .signature-col.right { text-align: right; }
        .signature-box { display: inline-block; text-align: center; min-width: 200px; }
        .signature-space { height: 45px; }
        .signature-name { font-weight: 700; text-decoration: underline; }

        .footer { border-top: 1px solid #d1d5db; font-size: 7pt; color: #6b7280; margin-top: 15px; padding-top: 4px; display: flex; justify-content: space-between; }

        @media print {
            body { font-size: 8pt; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @if (!empty($autoPrint))
        <div class="no-print" style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 8px 12px; margin-bottom: 12px; border-radius: 6px; text-align: center;">
            <p style="margin: 0 0 6px; font-weight: 600; color: #1e40af;">Halaman siap dicetak langsung dari peramban.</p>
            <button onclick="window.print()" style="background: #2563eb; color: #fff; border: 0; padding: 6px 16px; border-radius: 4px; font-size: 10pt; font-weight: 600; cursor: pointer;">Cetak Lembar Buku Induk (Ctrl + P)</button>
        </div>
    @endif

    {{-- HALAMAN 1: BIODATA LENGKAP --}}
    <div class="page">
        <header class="kop">
            @if ($logoDataUri)
                <img class="kop-logo" src="{{ $logoDataUri }}" alt="Logo {{ $institution->name }}">
            @endif
            <h1>KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET, DAN TEKNOLOGI</h1>
            <h2>{{ $institution->name }}</h2>
            <p>NPSN: {{ $institution->npsn ?: '—' }} | No. Izin Operasional: {{ $institution->permit_number ?: '—' }}</p>
            <p>{{ $institution->address }} @if($institution->phone) · Telp: {{ $institution->phone }} @endif @if($institution->email) · Email: {{ $institution->email }} @endif</p>
        </header>

        <div class="title">
            <h3>LEMBAR BUKU INDUK PESERTA DIDIK</h3>
            <p>NOMOR INDUK SATUAN (NIPD): <strong>{{ $student->student_number }}</strong> | NISN: <strong>{{ $student->national_student_number ?: '—' }}</strong></p>
        </div>

        {{-- BAGIAN A & PAS FOTO DALAM DUA KOLOM (DIBERIKAN JARAK/PADDING AGAR TIDAK MEPET) --}}
        <div class="profile-two-col">
            <div class="profile-col-left">
                <div class="section-header">A. Keterangan Tentang Diri Peserta Didik</div>
                <table class="grid">
                    <tr>
                        <td class="label">1. Nama Lengkap</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $student->full_name }}</td>
                    </tr>
                    <tr>
                        <td class="label">2. Nama Panggilan</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $student->nickname ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">3. NIS / NISN</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $student->student_number }} / {{ $student->national_student_number ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">4. Tempat, Tanggal Lahir</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $student->birth_place ?: '—' }}, {{ $student->birth_date ? $student->birth_date->translatedFormat('d F Y') : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">5. Jenis Kelamin</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $student->gender === 'L' ? 'Laki-laki' : ($student->gender === 'P' ? 'Perempuan' : '—') }}</td>
                    </tr>
                    <tr>
                        <td class="label">6. Agama</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $student->religion ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">7. NIK</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $student->nik ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">8. Nomor Kartu Keluarga (KK)</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $student->family_card_number ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">9. Kewarganegaraan</td>
                        <td class="separator">:</td>
                        <td class="value">{{ $student->citizenship ?: 'WNI' }}</td>
                    </tr>
                    <tr>
                        <td class="label">10. Anak ke- / Jumlah Saudara</td>
                        <td class="separator">:</td>
                        <td class="value">Anak ke-{{ $student->birth_order ?: '—' }} dari {{ $student->siblings_count !== null ? $student->siblings_count.' bersaudara' : '—' }}</td>
                    </tr>
                </table>
            </div>

            <div class="profile-col-right">
                <div class="photo-wrapper">
                    <table class="photo-table">
                        <tr>
                            <td class="photo-cell">
                                PAS FOTO<br>3 &times; 4
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="section-header">B. Keterangan Tempat Tinggal</div>
        <table class="grid">
            <tr>
                <td class="label">1. Alamat Jalan / Tempat Tinggal</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->address ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">2. Kode Pos</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->postal_code ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">3. Nomor Telepon / HP / WA</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->phone_number ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">4. Tinggal Bersama</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->living_with ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">5. Moda Transportasi</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->transportation_mode ?: '—' }}</td>
            </tr>
        </table>

        <div class="section-header">C. Keterangan Tentang Orang Tua Kandung & Wali</div>
        <table class="grid">
            <tr>
                <td class="label">1. Nama Ayah Kandung</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->father_name ?: '—' }} @if($student->father_nik) (NIK: {{ $student->father_nik }}) @endif</td>
            </tr>
            <tr>
                <td class="label">2. Tempat, Tgl Lahir Ayah</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->father_birth_place ?: '—' }}{{ $student->father_birth_date ? ', '.$student->father_birth_date->format('d/m/Y') : '' }}</td>
            </tr>
            <tr>
                <td class="label">3. Pendidikan & Pekerjaan Ayah</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->father_education ?: '—' }} / {{ $student->father_occupation ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">4. Nama Ibu Kandung</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->mother_name ?: '—' }} @if($student->mother_nik) (NIK: {{ $student->mother_nik }}) @endif</td>
            </tr>
            <tr>
                <td class="label">5. Tempat, Tgl Lahir Ibu</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->mother_birth_place ?: '—' }}{{ $student->mother_birth_date ? ', '.$student->mother_birth_date->format('d/m/Y') : '' }}</td>
            </tr>
            <tr>
                <td class="label">6. Pendidikan & Pekerjaan Ibu</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->mother_education ?: '—' }} / {{ $student->mother_occupation ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">7. Nama Wali (jika ada)</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->guardian_name ?: '—' }} @if($student->guardian_relation) ({{ $student->guardian_relation }}) @endif</td>
            </tr>
            <tr>
                <td class="label">8. Pekerjaan & Kontak Wali</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->guardian_occupation ?: '—' }} @if($student->guardian_phone) / Telp: {{ $student->guardian_phone }} @endif</td>
            </tr>
        </table>

        <div class="section-header">D. Keterangan Pendidikan Sebelumnya & Penerimaan</div>
        <table class="grid">
            <tr>
                <td class="label">1. Sekolah / Satuan Asal</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->previous_school_name ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">2. Nomor Ijazah / SKL Sebelumnya</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->previous_diploma_number ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">3. Tanggal Diterima Masuk</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->entry_date ? $student->entry_date->translatedFormat('d F Y') : ($student->created_at ? $student->created_at->translatedFormat('d F Y') : '—') }}</td>
            </tr>
            <tr>
                <td class="label">4. Nomor Seri Ijazah PKBM (Kelulusan)</td>
                <td class="separator">:</td>
                <td class="value">{{ $student->graduation_certificate_number ?: '—' }}</td>
            </tr>
        </table>

        <div class="signatures">
            <div class="signature-col"></div>
            <div class="signature-col right">
                <div class="signature-box">
                    <p>{{ $institution->city_name ?? 'Ditetapkan' }}, {{ now()->translatedFormat('d F Y') }}</p>
                    <p>Kepala {{ $institution->name }}</p>
                    <div class="signature-space"></div>
                    <p class="signature-name">{{ $institution->headmaster_name ?: '—' }}</p>
                    @if ($institution->headmaster_nip)<p style="font-size: 7.5pt; margin: 0;">NIP. {{ $institution->headmaster_nip }}</p>@endif
                </div>
            </div>
        </div>

        <div class="footer">
            <span>Buku Induk Peserta Didik — {{ $institution->name }}</span>
            <span>Hal. 1 (Biodata Resmi)</span>
            <span>Dicetak: {{ now()->translatedFormat('d/m/Y H:i') }}</span>
        </div>
    </div>

    {{-- HALAMAN 2: REKAPITULASI CATATAN PERKEMBANGAN BELAJAR & NILAI AKADEMIK --}}
    <div class="page">
        <header class="kop" style="margin-bottom: 8px;">
            <h1>{{ $institution->name }}</h1>
            <p>REKAM JEJAK AKADEMIK & REKAP NILAI RAPOR BUKU INDUK</p>
            <p>Nama: <strong>{{ $student->full_name }}</strong> | NIPD: <strong>{{ $student->student_number }}</strong> | NISN: <strong>{{ $student->national_student_number ?: '—' }}</strong></p>
        </header>

        @if (empty($records))
            <p style="text-align: center; color: #6b7280; padding: 30px 0;">Belum ada riwayat rapor atau nilai yang tercatat untuk peserta didik ini.</p>
        @else
            @foreach ($records as $record)
                <div style="margin-bottom: 14px; page-break-inside: avoid;">
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 4px 8px; font-weight: 700; font-size: 8pt; display: flex; justify-content: space-between;">
                        <span>Tahun Pelajaran: {{ $record['academic_year'] }} · Semester {{ $record['semester'] }}</span>
                        <span>Kelas: {{ $record['class_name'] }} ({{ $record['program'] }} / {{ $record['level'] }})</span>
                        <span>Wali Kelas: {{ $record['homeroom_teacher'] }}</span>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 15%;">Kode Mapel</th>
                                <th style="width: 40%;">Mata Pelajaran</th>
                                <th style="width: 10%;">KKM</th>
                                <th style="width: 15%;">Nilai Akhir</th>
                                <th style="width: 15%;">Predikat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($no = 1)
                            @forelse ($record['grades'] as $grade)
                                <tr>
                                    <td class="center">{{ $no++ }}</td>
                                    <td class="center">{{ $grade->subject_code_snapshot ?: ($grade->subjectAssignment?->subject?->code ?: '—') }}</td>
                                    <td>{{ $grade->subject_name_snapshot ?: ($grade->subjectAssignment?->subject?->name ?: '—') }}</td>
                                    <td class="center">{{ $grade->minimum_score_snapshot ?: ($grade->kkm_snapshot ?: '—') }}</td>
                                    <td class="center" style="font-weight: 700;">{{ $grade->score !== null ? $grade->score : '—' }}</td>
                                    <td class="center">{{ $grade->predicate_snapshot ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="center" style="color: #6b7280;">Nilai belum dimasukkan pada periode ini.</td>
                                </tr>
                            @endforelse
                            <tr style="background: #f1f5f9; font-weight: 700;">
                                <td colspan="4" class="right">Rata-rata Nilai Semester :</td>
                                <td class="center">{{ $record['average'] ?? '—' }}</td>
                                <td class="center"></td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="font-size: 7.5pt; display: table; width: 100%; border: 1px solid #e2e8f0; padding: 4px 6px; background: #fff;">
                        <div style="display: table-cell; width: 40%;">
                            <strong>Kehadiran:</strong> 
                            Sakit: {{ $record['sick'] ?? 0 }} hari · 
                            Izin: {{ $record['leave'] ?? 0 }} hari · 
                            Alpa: {{ $record['unexcused'] ?? 0 }} hari
                        </div>
                        <div style="display: table-cell; width: 60%; text-align: right;">
                            @if (!empty($record['decision']))
                                <strong>Keputusan Akhir:</strong> <span style="color: #1e3a8a; font-weight: 700;">{{ $record['decision'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        <div class="signatures">
            <div class="signature-col"></div>
            <div class="signature-col right">
                <div class="signature-box">
                    <p>{{ $institution->city_name ?? 'Ditetapkan' }}, {{ now()->translatedFormat('d F Y') }}</p>
                    <p>Kepala {{ $institution->name }}</p>
                    <div class="signature-space"></div>
                    <p class="signature-name">{{ $institution->headmaster_name ?: '—' }}</p>
                    @if ($institution->headmaster_nip)<p style="font-size: 7.5pt; margin: 0;">NIP. {{ $institution->headmaster_nip }}</p>@endif
                </div>
            </div>
        </div>

        <div class="footer">
            <span>Buku Induk Peserta Didik — {{ $institution->name }}</span>
            <span>Hal. 2 (Catatan Perkembangan Belajar)</span>
            <span>Dicetak: {{ now()->translatedFormat('d/m/Y H:i') }}</span>
        </div>
    </div>
</body>
</html>
