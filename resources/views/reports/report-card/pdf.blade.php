<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rapor {{ $identity['student']['name'] ?? 'Peserta Didik' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: {{ max(5, (int) $settings->margin_top_mm) }}mm {{ max(5, (int) $settings->margin_right_mm) }}mm {{ max(5, (int) $settings->margin_bottom_mm) }}mm {{ max(5, (int) $settings->margin_left_mm) }}mm;
        }

        :root { color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 10pt; }
        * { box-sizing: border-box; }
        body { margin: 0; line-height: 1.35; }
        h1, h2, h3, p { margin: 0; }
        .document { width: 100%; }
        .header { border-bottom: 2px solid #111827; margin-bottom: 10px; padding-bottom: 8px; text-align: center; }
        .header-logo { margin-bottom: 5px; text-align: {{ match ($settings->logo_position) { 'left' => 'left', 'right' => 'right', default => 'center' } }}; }
        .header-logo img { display: inline-block; height: auto; max-width: {{ max(10, (int) $settings->logo_width_mm) }}mm; }
        .institution-name { font-size: 16pt; font-weight: 700; text-transform: uppercase; }
        .institution-meta { font-size: 8.5pt; margin-top: 2px; }
        .custom-header { font-size: 9pt; margin-top: 5px; white-space: pre-line; }
        .title { font-size: 14pt; font-weight: 700; margin: 10px 0 2px; text-align: center; text-transform: uppercase; }
        .subtitle { font-size: 9pt; text-align: center; }
        .identity { border: 1px solid #374151; border-collapse: collapse; margin: 10px 0; width: 100%; }
        .identity td { padding: 4px 6px; vertical-align: top; }
        .identity .label { color: #374151; width: 16%; }
        .identity .value { font-weight: 600; width: 34%; }
        .section-title { background: #e5e7eb; border: 1px solid #374151; font-size: 10pt; font-weight: 700; margin-top: 10px; padding: 5px 7px; }
        table.data { border: 1px solid #374151; border-collapse: collapse; table-layout: fixed; width: 100%; }
        table.data th, table.data td { border: 1px solid #374151; padding: 4px 5px; overflow-wrap: anywhere; vertical-align: top; }
        table.data th:last-child, table.data td:last-child { border-right: 1px solid #374151; }
        table.data th { background: #f3f4f6; font-weight: 700; text-align: center; }
        .col-no { width: 6%; text-align: center; }
        .col-subject { width: 26%; }
        .col-score { width: 10%; text-align: center; }
        .col-predicate { width: 11%; text-align: center; }
        .col-kkm { width: 10%; text-align: center; }
        .text-block { overflow-wrap: anywhere; white-space: pre-line; }
        .two-column { display: table; margin-top: 10px; table-layout: fixed; width: 100%; }
        .two-column > section { display: table-cell; padding-right: 5px; vertical-align: top; width: 50%; }
        .two-column > section:last-child { padding-left: 5px; padding-right: 0; }
        .compact td, .compact th { padding: 4px; }
        .signatures { break-inside: avoid; display: table; margin-top: 22px; table-layout: fixed; width: 100%; }
        .signature { display: table-cell; padding: 0 8px; text-align: center; vertical-align: bottom; width: 33.333%; }
        .signature + .signature { border-left: 0; }
        .signature-label { min-height: 16px; }
        .signature-image { height: 38px; margin: 4px auto; object-fit: contain; max-width: 80px; }
        .signature-space { height: 46px; }
        .signature-name { font-weight: 700; text-decoration: underline; }
        .signature-number { font-size: 8.5pt; }
        .footer { border-top: 1px solid #9ca3af; color: #4b5563; font-size: 8pt; margin-top: 12px; padding-top: 4px; text-align: center; }
        .avoid-break { break-inside: avoid; page-break-inside: avoid; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        @media print {
            .section-title { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table.data th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }

        @media screen {
            body { background: #e5e7eb; padding: 24px; }
            .document {
                background: #ffffff;
                box-shadow: 0 10px 25px rgb(15 23 42 / 0.12);
                margin: 0 auto;
                min-height: 297mm;
                padding: {{ max(5, (int) $settings->margin_top_mm) }}mm {{ max(5, (int) $settings->margin_right_mm) }}mm {{ max(5, (int) $settings->margin_bottom_mm) }}mm {{ max(5, (int) $settings->margin_left_mm) }}mm;
                width: 210mm;
            }

        }

        @media screen and (max-width: 700px) {
            body { padding: 8px; }
            .document { width: 100%; }
        }
    </style>
</head>
<body>
<main class="document">
    <header class="header">
        @if ($logoDataUri)
            <div class="header-logo"><img src="{{ $logoDataUri }}" alt="Logo {{ $identity['institution']['name'] ?? 'PKBM' }}"></div>
        @endif
        <div class="institution-name">{{ $identity['institution']['name'] ?? 'PKBM' }}</div>
        <div class="institution-meta">
            {{ $identity['institution']['address'] ?? '' }}
            @if (!empty($identity['institution']['phone'])) · {{ $identity['institution']['phone'] }} @endif
            @if (!empty($identity['institution']['email'])) · {{ $identity['institution']['email'] }} @endif
        </div>
        @if ($settings->header_text)
            <div class="custom-header">{{ $settings->header_text }}</div>
        @endif
        <div class="title">Pencapaian Kompetensi Peserta Didik</div>
        <div class="subtitle">Rapor Pendidikan Kesetaraan PKBM</div>
    </header>

    <table class="identity">
        <tr>
            <td class="label">Nama peserta didik</td>
            <td class="value">{{ $studentName ?: '-' }}</td>
            <td class="label">Kelas</td>
            <td class="value">{{ $identity['class']['name'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NIPD / NISN</td>
            <td class="value">{{ $identity['student']['student_number'] ?? '-' }} / {{ $identity['student']['nisn'] ?? '-' }}</td>
            <td class="label">Semester</td>
            <td class="value">{{ $identity['semester']['name'] ?? ($identity['semester']['code'] ?? '-') }}</td>
        </tr>
        <tr>
            <td class="label">Nama sekolah</td>
            <td class="value">{{ $identity['institution']['name'] ?? '-' }}</td>
            <td class="label">Tahun pelajaran</td>
            <td class="value">{{ $identity['academic_year']['name'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat sekolah</td>
            <td class="value">{{ $identity['institution']['address'] ?? '-' }}</td>
            <td class="label">Nomor dokumen</td>
            <td class="value">{{ $documentNumber ?: '-' }}</td>
        </tr>
        <tr><td class="label">Tanggal terbit</td><td class="value" colspan="3">{{ $issuedDate ?: '-' }}</td></tr>
    </table>

    <div class="section-title">A. Capaian kompetensi</div>
    <table class="data">
        <thead>
        <tr>
            <th class="col-no">No.</th>
            <th class="col-subject">Mata pelajaran</th>
            @if ($settings->show_kkm)<th class="col-kkm">KKM</th>@endif
            <th class="col-score">Nilai</th>
            @if ($settings->show_predicate)<th class="col-predicate">Predikat</th>@endif
            <th>Deskripsi capaian kompetensi</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($grades as $index => $grade)
            <tr>
                <td class="col-no">{{ $index + 1 }}</td>
                <td><strong>{{ $grade->subject_name_snapshot }}</strong></td>
                @if ($settings->show_kkm)<td class="col-kkm">{{ $grade->minimum_score_snapshot }}</td>@endif
                <td class="col-score">{{ $grade->score ?? '-' }}</td>
                @if ($settings->show_predicate)<td class="col-predicate">{{ $grade->predicate ?? '-' }}</td>@endif
                <td class="text-block">{{ $grade->description ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ 4 + ($settings->show_kkm ? 1 : 0) + ($settings->show_predicate ? 1 : 0) }}">Belum ada data nilai.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="section-title">B. Kompetensi sikap</div>
    <table class="data compact">
        <tr><th style="width: 17%">Aspek</th><th>Deskripsi</th></tr>
        <tr><td>Sikap spiritual</td><td class="text-block">{{ $attitudes->get('SPIRITUAL')?->description ?: '-' }}</td></tr>
        <tr><td>Sikap sosial</td><td class="text-block">{{ $attitudes->get('SOCIAL')?->description ?: '-' }}</td></tr>
    </table>

    <div class="two-column">
        <section>
            <div class="section-title">C. Ekstrakurikuler</div>
            <table class="data">
                <thead><tr><th style="width: 14%">No.</th><th style="width: 36%">Kegiatan</th><th>Deskripsi</th></tr></thead>
                <tbody>
                @forelse ($extracurriculars as $index => $activity)
                    <tr><td class="col-no">{{ $index + 1 }}</td><td>{{ $activity->activity_name }}</td><td class="text-block">{{ $activity->description ?: '-' }}</td></tr>
                @empty
                    <tr><td colspan="3">Tidak ada kegiatan ekstrakurikuler.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
        <section>
            <div class="section-title">D. Ketidakhadiran</div>
            <table class="data compact">
                <tr><th>Alasan</th><th style="width: 30%">Hari</th></tr>
                <tr><td>Sakit</td><td>{{ $attendance?->sick_days ?? 0 }}</td></tr>
                <tr><td>Izin</td><td>{{ $attendance?->leave_days ?? 0 }}</td></tr>
                <tr><td>Tanpa keterangan</td><td>{{ $attendance?->unexcused_days ?? 0 }}</td></tr>
            </table>
        </section>
    </div>

    <div class="section-title">E. Saran-Saran</div>
    <div class="avoid-break" style="border: 1px solid #374151; min-height: 42px; padding: 7px;">
        <div class="text-block">{{ $homeroomNote?->note ?: '-' }}</div>
    </div>

    @if(filled($decisionLabel ?? null))
        <div class="avoid-break" style="margin-top: 10px; border: 1px solid #374151; padding: 7px;"><strong>Keputusan Wali Kelas:</strong> {{ $decisionLabel }}</div>
    @endif

    <div class="signatures">
        @foreach ($signatureSlots as $signature)
            <div class="signature">
                <div class="signature-label">{{ $signature['label'] }}</div>
                @if ($signature['imageDataUri'])
                    <img class="signature-image" src="{{ $signature['imageDataUri'] }}" alt="Tanda tangan">
                @else
                    <div class="signature-space"></div>
                @endif
                @if (($signature['type'] ?? null) !== 'PARENT_GUARDIAN' && filled($signature['name'] ?? null))
                    <div class="signature-name">{{ $signature['name'] }}</div>
                @else
                    <div>(........................................)</div>
                @endif
                @if (($signature['type'] ?? null) !== 'PARENT_GUARDIAN' && $signature['employee_number'])<div class="signature-number">{{ $signature['employee_number'] }}</div>@endif
            </div>
        @endforeach
    </div>

    <footer class="footer">{{ $footerText }}</footer>
</main>
</body>
</html>
