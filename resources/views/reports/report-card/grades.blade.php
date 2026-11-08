<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nilai {{ $studentName ?: 'Peserta Didik' }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { color: #111827; font: 9.5pt Arial, Helvetica, sans-serif; margin: 0; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 8px; text-align: center; }
        .logo { max-height: 20mm; max-width: 30mm; }
        h1 { font-size: 15pt; margin: 6px 0 2px; text-transform: uppercase; }
        h2 { font-size: 12pt; margin: 15px 0 8px; text-align: center; text-transform: uppercase; }
        .meta { color: #374151; font-size: 8.5pt; }
        .identity, .grades { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .identity { margin: 10px 0 14px; }
        .identity td { border: 1px solid #374151; padding: 5px 6px; vertical-align: top; }
        .identity .label { background: #f3f4f6; font-weight: 700; width: 18%; }
        .identity .value { width: 32%; }
        .grades-frame { border: 2px solid #374151; overflow: hidden; width: 100%; }
        .grades { border: 0; }
        .grades th, .grades td { border: 1px solid #374151; padding: 5px 6px; vertical-align: top; }
        .grades th:last-child, .grades td:last-child { border-right: 0; }
        .grades th { background: #f3f4f6; text-align: center; }
        .center { text-align: center; }
        .description { overflow-wrap: anywhere; white-space: pre-line; }
        .footer { border-top: 1px solid #9ca3af; color: #4b5563; display: table; font-size: 8pt; margin-top: 12px; padding-top: 4px; table-layout: fixed; width: 100%; }
        .footer-meta { display: table-cell; text-align: left; vertical-align: middle; }
        .footer-title { display: table-cell; text-align: right; vertical-align: middle; width: 40%; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
    </style>
</head>
<body>
    <header class="header">
        @if ($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Logo {{ $identity['institution']['name'] ?? 'PKBM' }}">@endif
        <h1>{{ $identity['institution']['name'] ?? 'PKBM' }}</h1>
        <div class="meta">{{ $identity['institution']['address'] ?? '' }} @if (!empty($identity['institution']['phone'])) · {{ $identity['institution']['phone'] }} @endif</div>
        @if ($settings->header_text)<div class="meta">{{ $settings->header_text }}</div>@endif
    </header>

    <h2>Daftar Nilai Peserta Didik</h2>
    <table class="identity">
        <tr><td class="label">Nama peserta didik</td><td class="value">{{ $studentName ?: '-' }}</td><td class="label">Kelas</td><td class="value">{{ $identity['class']['name'] ?? '-' }}</td></tr>
        <tr><td class="label">NIPD / NISN</td><td class="value">{{ $identity['student']['student_number'] ?? '-' }} / {{ $identity['student']['nisn'] ?? '-' }}</td><td class="label">Periode</td><td class="value">{{ $identity['academic_year']['name'] ?? '-' }} · {{ $identity['semester']['name'] ?? ($identity['semester']['code'] ?? '-') }}</td></tr>
    </table>

    <div class="grades-frame" aria-label="Daftar nilai peserta didik">
    <table class="grades">
        <thead><tr><th style="width: 6%">No.</th><th style="width: 27%">Mata pelajaran</th>@if ($settings->show_kkm)<th style="width: 10%">KKM</th>@endif<th style="width: 12%">Nilai</th>@if ($settings->show_predicate)<th style="width: 12%">Predikat</th>@endif<th>Deskripsi capaian</th></tr></thead>
        <tbody>
        @forelse ($grades as $index => $grade)
            <tr><td class="center">{{ $index + 1 }}</td><td><strong>{{ $grade->subject_name_snapshot }}</strong></td>@if ($settings->show_kkm)<td class="center">{{ $grade->minimum_score_snapshot ?? '-' }}</td>@endif<td class="center">{{ $grade->score ?? '-' }}</td>@if ($settings->show_predicate)<td class="center">{{ $grade->predicate ?? $grade->predicate_snapshot ?? '-' }}</td>@endif<td class="description">{{ $grade->description ?: '-' }}</td></tr>
        @empty
            <tr><td colspan="{{ 4 + ($settings->show_kkm ? 1 : 0) + ($settings->show_predicate ? 1 : 0) }}" class="center">Belum ada nilai.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>

    <footer class="footer">
        <div class="footer-meta">
            <span>{{ $studentName ?: '-' }}</span>
            @if (!empty($studentIdentifier ?? ($identity['student']['student_number'] ?? null)))
                <span> | {{ $studentIdentifier ?? ($identity['student']['student_number'] ?? '') }}</span>
            @endif
            @if (!empty($identity['class']['name']))
                <span> | {{ $identity['class']['name'] }}</span>
            @endif
        </div>
        @if (!empty($footerText))
            <div class="footer-title">{{ $footerText }}</div>
        @endif
    </footer>
</body>
</html>
