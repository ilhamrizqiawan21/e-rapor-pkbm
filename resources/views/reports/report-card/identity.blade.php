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
        .identity-list { width: 100%; }
        .identity-row { border-bottom: 1px solid #d1d5db; display: block; min-height: 34px; }
        .identity-row:last-child { border-bottom: 0; }
        .identity-label, .identity-value { display: inline-block; padding: 8px 0; vertical-align: top; }
        .identity-label { color: #374151; font-weight: 700; width: 32%; }
        .identity-label::after { content: ':'; }
        .identity-value { padding-left: 12px; width: 67%; }
        .address { min-height: 48px; white-space: pre-line; }
        .muted { color: #4b5563; font-size: 8.5pt; }
        .footer { border-top: 1px solid #9ca3af; color: #4b5563; font-size: 8pt; margin-top: 34px; padding-top: 6px; text-align: center; }
    </style>
</head>
<body>
    <header class="header">
        @if ($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Logo {{ $identity['institution']['name'] ?? 'PKBM' }}">@endif
        <h1>{{ $identity['institution']['name'] ?? 'PKBM' }}</h1>
        <div class="meta">{{ $identity['institution']['address'] ?? '' }} @if (!empty($identity['institution']['postal_code'])) · {{ $identity['institution']['postal_code'] }} @endif @if (!empty($identity['institution']['phone'])) · {{ $identity['institution']['phone'] }} @endif</div>
        @if ($settings->header_text)<div class="meta">{{ $settings->header_text }}</div>@endif
    </header>

    <h2>Identitas Peserta Didik</h2>
    @php($gender = match ($identity['student']['gender'] ?? null) { 'L' => 'Laki-laki', 'P' => 'Perempuan', default => '-' })
    <div class="identity-list" aria-label="Data identitas peserta didik">
        <div class="identity-row"><div class="identity-label">Nama peserta didik</div><div class="identity-value">{{ $studentName ?: '-' }}</div></div>
        <div class="identity-row"><div class="identity-label">NIPD / nomor induk</div><div class="identity-value">{{ $identity['student']['student_number'] ?? '-' }}</div></div>
        <div class="identity-row"><div class="identity-label">NISN</div><div class="identity-value">{{ $identity['student']['nisn'] ?? '-' }}</div></div>
        <div class="identity-row"><div class="identity-label">Jenis kelamin</div><div class="identity-value">{{ $gender }}</div></div>
        <div class="identity-row"><div class="identity-label">Tempat, tanggal lahir</div><div class="identity-value">{{ $identity['student']['birth_place'] ?? '-' }}{{ !empty($identity['student']['birth_date']) ? ', '.$identity['student']['birth_date'] : '' }}</div></div>
        <div class="identity-row"><div class="identity-label">Orang tua / wali</div><div class="identity-value">{{ $identity['student']['guardian_name'] ?? '-' }}</div></div>
        <div class="identity-row"><div class="identity-label">Alamat peserta didik</div><div class="identity-value address">{{ $identity['student']['address'] ?? '-' }}</div></div>
        <div class="identity-row"><div class="identity-label">Program</div><div class="identity-value">{{ $identity['class']['program'] ?? '-' }}</div></div>
        <div class="identity-row"><div class="identity-label">Kelas / tingkat</div><div class="identity-value">{{ $identity['class']['name'] ?? '-' }} / {{ $identity['class']['level'] ?? '-' }}</div></div>
        <div class="identity-row"><div class="identity-label">Semester</div><div class="identity-value">{{ $identity['semester']['name'] ?? ($identity['semester']['code'] ?? '-') }}</div></div>
        <div class="identity-row"><div class="identity-label">Tahun pelajaran</div><div class="identity-value">{{ $identity['academic_year']['name'] ?? '-' }}</div></div>
        <div class="identity-row"><div class="identity-label">Nomor dokumen</div><div class="identity-value">{{ $documentNumber ?: '-' }}</div></div>
        <div class="identity-row"><div class="identity-label">Nomor izin PKBM</div><div class="identity-value">{{ $identity['institution']['permit_number'] ?? '-' }}</div></div>
    </div>

    <p class="muted" style="margin-top: 8px;">Dokumen ini memuat identitas peserta didik berdasarkan data yang tersimpan pada periode rapor yang dipilih.</p>

    <div class="footer">{{ $footerText }}</div>
</body>
</html>
