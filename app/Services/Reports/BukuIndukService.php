<?php

namespace App\Services\Reports;

use App\Contracts\PdfEngine;
use App\Models\AcademicDecision;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class BukuIndukService
{
    public function __construct(
        private readonly PdfEngine $engine,
        private readonly NativeXlsxWriter $xlsx,
    ) {}

    /**
     * Prepares full profile and academic progression data for individual Buku Induk sheet.
     *
     * @return array<string, mixed>
     */
    public function studentData(Student $student): array
    {
        $student->loadMissing([
            'institution',
            'enrollments' => function ($query): void {
                $query->with([
                    'academicClass.program',
                    'academicClass.level',
                    'academicClass.academicYear',
                    'academicClass.homeroomTeacher',
                    'reportCards' => function ($reportQuery): void {
                        $reportQuery->with([
                            'semester',
                            'grades' => fn ($g) => $g->orderBy('display_order_snapshot')->orderBy('id'),
                            'attendanceSummary',
                            'homeroomNote',
                        ])->orderBy('academic_year_id')->orderBy('semester_id');
                    },
                ])->orderBy('start_date')->orderBy('id');
            },
        ]);

        $decisions = AcademicDecision::query()
            ->where('student_id', $student->getKey())
            ->get()
            ->keyBy(fn (AcademicDecision $d): string => $d->academic_year_id.'-'.$d->semester_id);

        $records = [];
        foreach ($student->enrollments as $enrollment) {
            $class = $enrollment->academicClass;
            if (! $class) {
                continue;
            }

            foreach ($enrollment->reportCards as $reportCard) {
                $grades = $reportCard->grades;
                $numericScores = $grades->pluck('score')->filter(fn ($s): bool => is_numeric($s))->map(fn ($s): float => (float) $s);
                $decisionKey = $class->academic_year_id.'-'.$reportCard->semester_id;
                $decision = $decisions->get($decisionKey);

                $records[] = [
                    'academic_year' => $class->academicYear?->name ?? '—',
                    'semester' => $reportCard->semester?->name ?? ($reportCard->semester?->code ?? '—'),
                    'class_name' => $class->name,
                    'program' => $class->program?->name ?? '—',
                    'level' => $class->level?->name ?? '—',
                    'homeroom_teacher' => $class->homeroomTeacher?->name ?? '—',
                    'grades' => $grades,
                    'average' => $numericScores->isNotEmpty() ? round($numericScores->avg(), 2) : null,
                    'sick' => $reportCard->attendanceSummary?->sick_days,
                    'leave' => $reportCard->attendanceSummary?->leave_days,
                    'unexcused' => $reportCard->attendanceSummary?->unexcused_days,
                    'note' => $reportCard->homeroomNote?->note,
                    'decision' => $decision?->label_snapshot ?? $decision?->decision,
                ];
            }
        }

        $institution = $student->institution;

        return [
            'student' => $student,
            'institution' => $institution,
            'logoDataUri' => $this->logoDataUri($institution),
            'records' => $records,
        ];
    }

    /**
     * Exports the Master Buku Register Induk to Excel (.xlsx).
     *
     * @param  Collection<int, Student>  $students
     * @return array{path: string, filename: string}
     */
    public function exportExcel(Institution $institution, Collection $students): array
    {
        $rows = [];

        // Judul
        $rows[] = [
            ['value' => 'BUKU REGISTER INDUK PESERTA DIDIK', 'style' => 2],
        ];
        $rows[] = [
            ['value' => 'SATUAN PENDIDIKAN: '.mb_strtoupper($institution->name).' (NPSN: '.($institution->npsn ?: '—').')', 'style' => 0],
        ];
        $rows[] = [
            ['value' => 'Alamat: '.($institution->address ?: '—').' | Tanggal Unduh: '.now()->translatedFormat('d F Y H:i'), 'style' => 0],
        ];
        $rows[] = []; // Baris kosong pemisah

        // Header Tabel Kolom (Row 5 -> freeze_row: 5)
        $headers = [
            'No',
            'NIPD / No. Induk',
            'NISN',
            'NIK',
            'No. KK',
            'Nama Lengkap',
            'L/P',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Agama',
            'Alamat Lengkap',
            'No. HP / WA',
            'Nama Ayah',
            'Pekerjaan Ayah',
            'Nama Ibu',
            'Pekerjaan Ibu',
            'Nama Wali',
            'Sekolah Asal',
            'No. Ijazah Sebelumnya',
            'Tgl Masuk PKBM',
            'Status',
            'No. Ijazah Lulus',
        ];

        $headerRow = [];
        foreach ($headers as $header) {
            $headerRow[] = ['value' => $header, 'style' => 1];
        }
        $rows[] = $headerRow;

        $no = 1;
        foreach ($students as $student) {
            $rows[] = [
                ['value' => $no++, 'style' => 5],
                ['value' => $student->student_number, 'style' => 4],
                ['value' => $student->national_student_number ?: '—', 'style' => 5],
                ['value' => $student->nik ?: '—', 'style' => 5],
                ['value' => $student->family_card_number ?: '—', 'style' => 5],
                ['value' => $student->full_name, 'style' => 4],
                ['value' => $student->gender ?: '—', 'style' => 5],
                ['value' => $student->birth_place ?: '—', 'style' => 4],
                ['value' => $student->birth_date ? $student->birth_date->format('d/m/Y') : '—', 'style' => 5],
                ['value' => $student->religion ?: '—', 'style' => 5],
                ['value' => $student->address ?: '—', 'style' => 4],
                ['value' => $student->phone_number ?: '—', 'style' => 5],
                ['value' => $student->father_name ?: '—', 'style' => 4],
                ['value' => $student->father_occupation ?: '—', 'style' => 4],
                ['value' => $student->mother_name ?: '—', 'style' => 4],
                ['value' => $student->mother_occupation ?: '—', 'style' => 4],
                ['value' => $student->guardian_name ?: '—', 'style' => 4],
                ['value' => $student->previous_school_name ?: '—', 'style' => 4],
                ['value' => $student->previous_diploma_number ?: '—', 'style' => 4],
                ['value' => $student->entry_date ? $student->entry_date->format('d/m/Y') : '—', 'style' => 5],
                ['value' => $student->status, 'style' => 5],
                ['value' => $student->graduation_certificate_number ?: '—', 'style' => 4],
            ];
        }

        $widths = [
            6.0,  // No
            18.0, // NIPD
            16.0, // NISN
            20.0, // NIK
            20.0, // KK
            32.0, // Nama
            6.0,  // JK
            18.0, // Tempat Lahir
            14.0, // Tanggal Lahir
            14.0, // Agama
            36.0, // Alamat
            16.0, // HP
            24.0, // Ayah
            18.0, // Pekerjaan Ayah
            24.0, // Ibu
            18.0, // Pekerjaan Ibu
            24.0, // Wali
            25.0, // Asal Sekolah
            22.0, // No Ijazah Asal
            14.0, // Tgl Masuk
            12.0, // Status
            22.0, // No Ijazah Kelulusan
        ];

        $sheets = [
            [
                'name' => 'Buku Register Induk',
                'rows' => $rows,
                'widths' => $widths,
                'freeze_row' => 5,
            ],
        ];

        $filename = 'buku-register-induk-'.Str::slug($institution->name).'-'.now()->format('Ymd-His').'.xlsx';
        $path = tempnam(sys_get_temp_dir(), 'buku_induk_xlsx_');
        if ($path === false) {
            throw new \RuntimeException('Gagal membuat file sementara untuk buku induk.');
        }

        $this->xlsx->write($sheets, $path);

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    /**
     * Renders individual student Buku Induk sheet into PDF.
     */
    public function sheetPdf(Student $student): string
    {
        $data = $this->studentData($student);
        $html = view('reports.buku-induk.sheet-pdf', $data)->render();

        return $this->engine->render($html);
    }

    /**
     * Renders master register table into PDF landscape.
     *
     * @param  Collection<int, Student>  $students
     */
    public function registerPdf(Institution $institution, Collection $students): string
    {
        $html = view('reports.buku-induk.register-pdf', [
            'institution' => $institution,
            'logoDataUri' => $this->logoDataUri($institution),
            'students' => $students,
        ])->render();

        return $this->engine->render($html);
    }

    private function logoDataUri(?Institution $institution): ?string
    {
        if ($institution === null || blank($institution->logo_path)) {
            return null;
        }

        $path = Storage::disk('public')->path((string) $institution->logo_path);
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
