<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Identitas {{ $studentName ?: 'Peserta Didik' }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm; }
        * { box-sizing: border-box; }
        body { color: #111827; font: 10.5pt Arial, Helvetica, sans-serif; margin: 0; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 10px; text-align: center; }
        .logo { max-height: 25mm; max-width: 35mm; }
        h1 { font-size: 17pt; margin: 8px 0 2px; text-transform: uppercase; }
        h2 { font-size: 13pt; margin: 28px 0 12px; text-align: center; text-transform: uppercase; }
        .meta { color: #374151; font-size: 9pt; }
        table { border-collapse: collapse; width: 100%; }
        td { border: 1px solid #374151; padding: 9px 10px; vertical-align: top; }
        td.label { background: #f3f4f6; font-weight: 700; width: 24%; }
        td.value { width: 26%; }
        .footer { border-top: 1px solid #9ca3af; color: #4b5563; font-size: 8pt; margin-top: 34px; padding-top: 6px; text-align: center; }
    </style>
</head>
<body>
    <header class="header">
        @if ($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Logo {{ $identity['institution']['name'] ?? 'PKBM' }}">@endif
        <h1>{{ $identity['institution']['name'] ?? 'PKBM' }}</h1>
        <div class="meta">{{ $identity['institution']['address'] ?? '' }} @if (!empty($identity['institution']['phone'])) · {{ $identity['institution']['phone'] }} @endif</div>
        @if ($settings->header_text)<div class="meta">{{ $settings->header_text }}</div>@endif
    </header>

    <h2>Identitas Peserta Didik</h2>
    <table>
        <tr><td class="label">Nama peserta didik</td><td class="value">{{ $studentName ?: '-' }}</td><td class="label">NIPD</td><td class="value">{{ $identity['student']['student_number'] ?? '-' }}</td></tr>
        <tr><td class="label">NISN</td><td class="value">{{ $identity['student']['nisn'] ?? '-' }}</td><td class="label">Program</td><td class="value">{{ $identity['class']['program'] ?? '-' }}</td></tr>
        <tr><td class="label">Kelas</td><td class="value">{{ $identity['class']['name'] ?? '-' }}</td><td class="label">Tingkat</td><td class="value">{{ $identity['class']['level'] ?? '-' }}</td></tr>
        <tr><td class="label">Semester</td><td class="value">{{ $identity['semester']['name'] ?? ($identity['semester']['code'] ?? '-') }}</td><td class="label">Tahun pelajaran</td><td class="value">{{ $identity['academic_year']['name'] ?? '-' }}</td></tr>
        <tr><td class="label">Nomor dokumen</td><td class="value">{{ $documentNumber ?: '-' }}</td><td class="label">Nomor izin PKBM</td><td class="value">{{ $identity['institution']['permit_number'] ?? '-' }}</td></tr>
    </table>

    <div class="footer">{{ $footerText }}</div>
</body>
</html>
