<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Leger Nilai PKBM</title>
    <style>
        @page { size: A4 landscape; margin: 9mm 8mm 10mm; }
        :root { color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 7.5pt; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        .ledger-page { page-break-after: always; width: 100%; }
        .ledger-page:last-child { page-break-after: auto; }
        h1, h2, p { margin: 0; }
        h1 { font-size: 13pt; text-align: center; text-transform: uppercase; }
        .meta { border-collapse: collapse; margin: 5px 0 7px; width: 100%; }
        .meta td { padding: 1px 4px; vertical-align: top; }
        .meta .label { font-weight: 700; width: 10%; }
        .meta .value { width: 25%; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        th, td { border: 1px solid #374151; overflow-wrap: anywhere; padding: 3px 3px; vertical-align: top; }
        th { background: #dbeafe; font-weight: 700; text-align: center; }
        .main th, .main td { font-size: 7pt; }
        .no { text-align: center; width: 3.5%; }
        .nipd { width: 8%; }
        .nisn { width: 8%; }
        .name { width: 17%; }
        .subject { text-align: center; width: 6%; }
        .average { text-align: center; width: 6%; }
        .attendance { text-align: center; width: 5%; }
        .note { width: 14%; }
        .section { font-size: 9pt; font-weight: 700; margin: 8px 0 3px; }
        .detail th, .detail td { font-size: 7pt; }
        .detail .description { width: 28%; }
        .footer { border-top: 1px solid #9ca3af; display: flex; justify-content: space-between; margin-top: 7px; padding-top: 3px; }
        .signatures { display: flex; justify-content: space-between; margin-top: 11px; page-break-inside: avoid; }
        .signature { text-align: center; width: 28%; }
        .signature-space { height: 24px; }
        .signature-name { font-weight: 700; text-decoration: underline; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        @media print {
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
@foreach ($classes as $data)
    <section class="ledger-page">
        <h1>Leger Nilai — {{ $data['class'] }}</h1>
        <table class="meta">
            <tr><td class="label">PKBM</td><td class="value">{{ $data['institution']['name'] }}</td><td class="label">Program</td><td class="value">{{ $data['program'] }}</td><td class="label">Tahun Pelajaran</td><td class="value">{{ $data['academic_year'] }}</td></tr>
            <tr><td class="label">Kelas</td><td class="value">{{ $data['class'] }}</td><td class="label">Semester</td><td class="value">{{ $data['semester'] }}</td><td class="label">Wali Kelas</td><td class="value">{{ $data['homeroom'] }}</td></tr>
        </table>

        <table class="main">
            <thead>
            <tr>
                <th class="no">No.</th><th class="nipd">NIPD</th><th class="nisn">NISN</th><th class="name">Nama Peserta Didik</th>
                @foreach ($data['subjects'] as $subject)<th class="subject">{{ $subject['label'] }}</th>@endforeach
                <th class="average">Rata-rata</th><th class="attendance">Sakit</th><th class="attendance">Izin</th><th class="attendance">Tanpa Ket.</th><th class="note">Keterangan</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($data['rows'] as $row)
                <tr>
                    <td class="no">{{ $row['no'] }}</td><td>{{ $row['student_number'] }}</td><td>{{ $row['nisn'] }}</td><td>{{ $row['student_name'] }}</td>
                    @foreach ($data['subjects'] as $subject)<td class="subject">{{ $row['scores'][$subject['key']] ?? '—' }}</td>@endforeach
                    <td class="average">{{ $row['average'] ?? '—' }}</td><td class="attendance">{{ $row['sick'] ?? '—' }}</td><td class="attendance">{{ $row['leave'] ?? '—' }}</td><td class="attendance">{{ $row['unexcused'] ?? '—' }}</td><td>{{ $row['note'] }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 8 + count($data['subjects']) }}">Belum ada rapor APPROVED/LOCKED untuk kelas ini.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="section">Detail nilai</div>
        <table class="detail">
            <thead><tr><th>NIPD</th><th>Nama Peserta Didik</th><th>Mata Pelajaran</th><th>KKM</th><th>Nilai</th><th>Predikat</th><th>Status Nilai</th><th>Guru Pengampu</th><th class="description">Deskripsi</th><th>Periode</th></tr></thead>
            <tbody>
            @forelse ($data['details'] as $detail)
                <tr><td>{{ $detail['student_number'] }}</td><td>{{ $detail['student_name'] }}</td><td>{{ $detail['subject'] }}</td><td style="text-align:center">{{ $detail['kkm'] ?? '—' }}</td><td style="text-align:center">{{ $detail['score'] ?? '—' }}</td><td style="text-align:center">{{ $detail['predicate'] }}</td><td style="text-align:center">{{ $detail['status'] }}</td><td>{{ $detail['teacher'] }}</td><td>{{ $detail['description'] }}</td><td>{{ $detail['period'] }}</td></tr>
            @empty
                <tr><td colspan="10">Belum ada detail nilai.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="signatures">
            <div class="signature"><div>Wali Kelas</div><div class="signature-space"></div><div class="signature-name">{{ $data['homeroom'] }}</div><div>{{ $data['homeroom_employee_number'] }}</div></div>
            <div class="signature"><div>Kepala PKBM</div><div class="signature-space"></div><div class="signature-name">{{ $data['headmaster'] }}</div></div>
        </div>
        <div class="footer"><span>Leger Nilai PKBM · Sumber: rapor APPROVED/LOCKED</span><span>Dicetak pada {{ $data['exported_at'] }}</span></div>
    </section>
@endforeach
</body>
</html>
