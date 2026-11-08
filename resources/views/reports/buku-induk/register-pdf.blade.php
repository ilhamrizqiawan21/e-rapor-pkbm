<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Buku Register Induk — {{ $institution->name }}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm 8mm 10mm; }
        * { box-sizing: border-box; }
        body { color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 7.5pt; margin: 0; }
        .kop { border-bottom: 2px solid #000; padding-bottom: 4px; margin-bottom: 8px; text-align: center; }
        .kop h1 { font-size: 11pt; margin: 0 0 2px; text-transform: uppercase; font-weight: 700; }
        .kop p { font-size: 7.5pt; margin: 0; color: #374151; }
        
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        th, td { border: 1px solid #4b5563; padding: 3px 4px; vertical-align: top; overflow-wrap: break-word; }
        th { background: #dbeafe; font-weight: 700; text-align: center; }
        td.center { text-align: center; }
        
        .footer { border-top: 1px solid #9ca3af; margin-top: 8px; padding-top: 4px; display: flex; justify-content: space-between; font-size: 7pt; color: #4b5563; }
        
        @media print {
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @if (!empty($autoPrint))
        <div class="no-print" style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 6px 10px; margin-bottom: 8px; text-align: center;">
            <button onclick="window.print()" style="background: #2563eb; color: #fff; border: 0; padding: 4px 12px; font-weight: 600; cursor: pointer;">Cetak Register (Ctrl + P)</button>
        </div>
    @endif

    <div class="kop">
        <h1>BUKU REGISTER INDUK PESERTA DIDIK</h1>
        <p><strong>{{ $institution->name }}</strong> (NPSN: {{ $institution->npsn ?: '—' }}) | Izin: {{ $institution->permit_number ?: '—' }}</p>
        <p>{{ $institution->address }} | Tanggal Cetak: {{ now()->translatedFormat('d F Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 7%;">NIPD</th>
                <th style="width: 7%;">NISN</th>
                <th style="width: 8%;">NIK</th>
                <th style="width: 14%;">Nama Lengkap</th>
                <th style="width: 3%;">L/P</th>
                <th style="width: 10%;">Tempat, Tgl Lahir</th>
                <th style="width: 5%;">Agama</th>
                <th style="width: 15%;">Alamat Lengkap</th>
                <th style="width: 10%;">Nama Orang Tua/Wali</th>
                <th style="width: 10%;">Sekolah Asal</th>
                <th style="width: 5%;">Status</th>
                <th style="width: 8%;">No. Ijazah Lulus</th>
            </tr>
        </thead>
        <tbody>
            @php($no = 1)
            @forelse ($students as $student)
                <tr>
                    <td class="center">{{ $no++ }}</td>
                    <td class="center font-bold">{{ $student->student_number }}</td>
                    <td class="center">{{ $student->national_student_number ?: '—' }}</td>
                    <td class="center">{{ $student->nik ?: '—' }}</td>
                    <td><strong>{{ $student->full_name }}</strong></td>
                    <td class="center">{{ $student->gender ?: '—' }}</td>
                    <td>{{ $student->birth_place ?: '—' }}{{ $student->birth_date ? ', '.$student->birth_date->format('d/m/Y') : '' }}</td>
                    <td class="center">{{ $student->religion ?: '—' }}</td>
                    <td>{{ $student->address ?: '—' }}</td>
                    <td>
                        {{ $student->father_name ?: ($student->mother_name ?: ($student->guardian_name ?: '—')) }}
                    </td>
                    <td>{{ $student->previous_school_name ?: '—' }}</td>
                    <td class="center">
                        @php($statusMap = ['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'GRADUATED' => 'Lulus', 'TRANSFERRED' => 'Pindah'])
                        {{ $statusMap[$student->status] ?? $student->status }}
                    </td>
                    <td class="center">{{ $student->graduation_certificate_number ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="center" style="padding: 16px; color: #6b7280;">Tidak ada data peserta didik yang cocok.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Buku Register Induk — {{ $institution->name }}</span>
        <span>Total: {{ $students->count() }} Peserta Didik</span>
        <span>Dicetak: {{ now()->translatedFormat('d/m/Y H:i') }}</span>
    </div>
</body>
</html>

