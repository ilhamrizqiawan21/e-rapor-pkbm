<?php

namespace App\Services;

use App\Enums\AcademicYearStatus;
use App\Enums\SemesterStatus;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\DescriptionSnippet;
use App\Models\Enrollment;
use App\Models\GradeImportBatch;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Support\SimpleXlsx;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owns the bounded, context-locked Excel workflow for grade entry.
 *
 * The workbook is only a transport format. Scores are previewed and then
 * written through GradeEntryService so predicates, descriptions, workflow
 * permissions, tenant boundaries, and locked report cards remain server-owned.
 */
final class GradeImportService
{
    public const TEMPLATE_VERSION = 'GRADE-INPUT-V1';

    private const MAX_ROWS = 5000;

    private const PREVIEW_TTL_MINUTES = 30;

    /** @var array<int, string> */
    private const GRADE_HEADERS = [
        'NO',
        'ENROLLMENT_ID',
        'NIPD',
        'NISN',
        'NAMA LENGKAP',
        'NILAI (0-100)',
        'PREDIKAT (SERVER)',
        'DESKRIPSI OTOMATIS (SERVER)',
        'STATUS (SERVER)',
    ];

    public function __construct(
        private readonly GradeEntryService $grades,
        private readonly PredicateRangeService $predicateRanges,
        private readonly DescriptionSnippetTemplateService $templates,
        private readonly DescriptionSnippetUsageService $snippets,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function templateResponse(User $actor, array $filters): StreamedResponse
    {
        $context = $this->resolveContext($actor, $filters);
        $fileName = 'template-nilai-'.Str::slug($context['academicClass']->name).'-'.Str::slug($context['assignment']->subject->name).'.xlsx';

        return response()->streamDownload(function () use ($context): void {
            $path = tempnam(sys_get_temp_dir(), 'grade-template-');
            if ($path === false) {
                throw new RuntimeException('Template nilai tidak dapat dibuat.');
            }

            try {
                SimpleXlsx::write($path, $this->templateSheets($context), [
                    'NILAI' => [5],
                ], [
                    'KONTEKS' => ['B7'],
                ]);
                readfile($path);
            } finally {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Parse and validate an uploaded workbook without persisting grades.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function preview(User $actor, UploadedFile $file, array $filters): array
    {
        $this->assertSafeFile($file);
        $context = $this->resolveContext($actor, $filters);
        $sheets = SimpleXlsx::read((string) $file->getRealPath());
        $normalizedSheets = [];
        foreach ($sheets as $name => $rows) {
            $normalizedSheets[$this->normalizeHeader($name)] = $rows;
        }

        foreach (['petunjuk', 'konteks', 'nilai'] as $sheetName) {
            if (! array_key_exists($sheetName, $normalizedSheets)) {
                throw new RuntimeException("Sheet {$sheetName} wajib ada dan tidak boleh diganti namanya.");
            }
        }

        $contextValues = $this->contextValues($normalizedSheets['konteks']);
        $snippet = $this->resolveSnippet($actor, $context['assignment'], $contextValues['bank_deskripsi_id'] ?? null);
        $this->assertWorkbookContext($context, $contextValues);
        $material = trim((string) ($contextValues['materi'] ?? ''));
        $material = $snippet?->material ?: $material;

        if ($snippet === null && collect($normalizedSheets['nilai'])->skip(1)->contains(fn (array $row): bool => trim((string) ($row[5] ?? '')) !== '' && $material === '')) {
            // The row-level pass below also reports this error. This early
            // condition only makes the common missing-material mistake clear.
            $materialError = 'Materi wajib diisi pada sheet KONTEKS sebelum nilai diimpor.';
        } else {
            $materialError = null;
        }

        $header = $normalizedSheets['nilai'][0] ?? [];
        $columns = $this->mapGradeHeaders($header);
        $enrollments = $this->enrollments($context['academicClass'], $context['academicYear']);
        $byId = $enrollments->keyBy('id');
        $byNipd = $enrollments->filter(fn (Enrollment $enrollment): bool => filled($enrollment->student?->student_number))->keyBy(fn (Enrollment $enrollment): string => (string) $enrollment->student->student_number);
        $seenEnrollmentIds = [];
        $rows = [];

        foreach (array_slice($normalizedSheets['nilai'], 1) as $index => $rawRow) {
            if ($this->blankRow($rawRow)) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                throw new RuntimeException('File nilai melebihi batas '.number_format(self::MAX_ROWS, 0, ',', '.').' baris.');
            }

            $rowNumber = $index + 2;
            $values = $this->rowValues($rawRow, $columns);
            $errors = [];
            $enrollment = $byId->get((int) $values['enrollment_id']);
            if ($enrollment === null && $values['nipd'] !== '') {
                $enrollment = $byNipd->get($values['nipd']);
            }

            if ($enrollment === null) {
                $errors[] = 'Enrollment ID atau NIPD tidak terdaftar pada kelas dan periode ini.';
            } else {
                if (isset($seenEnrollmentIds[$enrollment->getKey()])) {
                    $errors[] = 'Peserta didik muncul lebih dari satu kali pada file.';
                }
                $seenEnrollmentIds[$enrollment->getKey()] = true;
                $student = $enrollment->student;
                if ((string) $enrollment->getKey() !== $values['enrollment_id']) {
                    $errors[] = 'Enrollment ID tidak sesuai dengan NIPD pada template.';
                }
                if ((string) $student->student_number !== $values['nipd']) {
                    $errors[] = 'NIPD tidak sesuai dengan data server.';
                }
                if ((string) ($student->national_student_number ?? '') !== $values['nisn']) {
                    $errors[] = 'NISN tidak sesuai dengan data server.';
                }
                if (mb_strtolower(trim($student->full_name)) !== mb_strtolower(trim($values['full_name']))) {
                    $errors[] = 'Nama peserta didik tidak sesuai dengan data server.';
                }
            }

            $score = $this->parseScore($values['score'], $errors);
            if ($score !== null && $snippet === null && $material === '') {
                $errors[] = $materialError ?? 'Materi wajib diisi sebelum nilai diimpor.';
            }

            $predicate = $score === null ? null : $this->predicateRanges->predicateFor($actor->institution, $score);
            if ($score !== null && $predicate === null) {
                $errors[] = 'Nilai tidak memiliki predikat aktif pada konfigurasi institusi.';
            }
            $description = $predicate === null
                ? null
                : ($snippet !== null
                    ? $this->templates->render($snippet, $score, $predicate->code)
                    : sprintf('Siswa %s dalam %s.', Str::lower($predicate->label), $material));

            $rows[] = [
                'row' => $rowNumber,
                'enrollment_id' => $enrollment?->getKey(),
                'nipd' => $enrollment?->student?->student_number ?? $values['nipd'],
                'nisn' => $enrollment?->student?->national_student_number ?? $values['nisn'],
                'full_name' => $enrollment?->student?->full_name ?? $values['full_name'],
                'score' => $score,
                'predicate' => $predicate?->code,
                'predicate_label' => $predicate?->label,
                'description' => $description,
                'errors' => array_values(array_unique($errors)),
                'valid' => $errors === [],
                'action' => $score === null ? 'SKIP' : 'UPDATE',
            ];
        }

        $validCount = count(array_filter($rows, static fn (array $row): bool => $row['valid']));
        $invalidCount = count($rows) - $validCount;
        $updateCount = count(array_filter($rows, static fn (array $row): bool => $row['valid'] && $row['score'] !== null));
        $preview = [
            'template_version' => self::TEMPLATE_VERSION,
            'file_name' => $file->getClientOriginalName(),
            'context' => [
                'academic_year_id' => $context['academicYear']->getKey(),
                'academic_year_name' => $context['academicYear']->name,
                'semester_id' => $context['semester']->getKey(),
                'semester_name' => $context['semester']->name,
                'class_id' => $context['academicClass']->getKey(),
                'class_name' => $context['academicClass']->name,
                'subject_assignment_id' => $context['assignment']->getKey(),
                'subject_name' => $context['assignment']->subject->name,
                'teacher_name' => $context['assignment']->teacher?->name,
            ],
            'material' => $material,
            'description_snippet_id' => $snippet?->getKey(),
            'rows' => $rows,
            'total_count' => count($rows),
            'valid_count' => $validCount,
            'invalid_count' => $invalidCount,
            'update_count' => $updateCount,
            'skip_count' => count($rows) - $updateCount,
            'errors' => array_values(array_map(static fn (array $row): array => [
                'row' => $row['row'],
                'errors' => $row['errors'],
                'values' => [
                    'ENROLLMENT_ID' => (string) ($row['enrollment_id'] ?? ''),
                    'NIPD' => $row['nipd'],
                    'NISN' => $row['nisn'],
                    'NAMA LENGKAP' => $row['full_name'],
                    'NILAI (0-100)' => (string) ($row['score'] ?? ''),
                ],
            ], array_filter($rows, static fn (array $row): bool => $row['errors'] !== []))),
        ];

        return $preview;
    }

    /** @param  array<string, mixed>  $preview */
    public function storePreview(User $actor, array $preview): GradeImportBatch
    {
        $context = $preview['context'];
        $token = bin2hex(random_bytes(32));

        $batch = GradeImportBatch::query()->create([
            'institution_id' => $actor->institution_id,
            'uploaded_by_user_id' => $actor->getKey(),
            'academic_year_id' => $context['academic_year_id'],
            'semester_id' => $context['semester_id'],
            'academic_class_id' => $context['class_id'],
            'subject_assignment_id' => $context['subject_assignment_id'],
            'token' => $token,
            'file_name' => $preview['file_name'] ?? null,
            'material' => $preview['material'] ?? null,
            'preview_json' => $preview,
            'status' => 'PREVIEWED',
            'total_rows' => $preview['total_count'] ?? 0,
            'valid_rows' => $preview['valid_count'] ?? 0,
            'invalid_rows' => $preview['invalid_count'] ?? 0,
        ]);

        $this->audit->record(
            $actor,
            'grade_import.previewed',
            GradeImportBatch::class,
            $batch->getKey(),
            null,
            [
                'status' => 'PREVIEWED',
                'file_name' => $batch->file_name,
                'total_rows' => $batch->total_rows,
                'valid_rows' => $batch->valid_rows,
                'invalid_rows' => $batch->invalid_rows,
            ],
        );

        return $batch;
    }

    public function loadPreview(User $actor, string $token): ?GradeImportBatch
    {
        if (! preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            return null;
        }

        $batch = GradeImportBatch::query()
            ->where('institution_id', $actor->institution_id)
            ->where('uploaded_by_user_id', $actor->getKey())
            ->where('token', $token)
            ->first();

        if ($batch === null || $batch->created_at?->lt(now()->subMinutes(self::PREVIEW_TTL_MINUTES)) || $batch->status !== 'PREVIEWED') {
            return null;
        }

        return $batch;
    }

    /**
     * Import is atomic. A stale enrollment, changed permission, locked card,
     * or concurrency conflict rejects the complete batch instead of leaving a
     * teacher with a partially imported class.
     */
    public function import(User $actor, GradeImportBatch $batch): int
    {
        if ($batch->institution_id !== $actor->institution_id || $batch->uploaded_by_user_id !== $actor->getKey() || $batch->status !== 'PREVIEWED') {
            throw new RuntimeException('Preview impor tidak tersedia untuk pengguna ini.');
        }
        if ($batch->created_at?->lt(now()->subMinutes(self::PREVIEW_TTL_MINUTES))) {
            throw new RuntimeException('Preview impor sudah kedaluwarsa. Unduh template dan lakukan preview ulang.');
        }

        $preview = $batch->preview_json;
        if (($preview['invalid_count'] ?? 0) > 0) {
            throw new RuntimeException('Perbaiki semua baris yang ditandai merah sebelum mengimpor.');
        }

        $context = $this->resolveContext($actor, [
            'academic_year_id' => $batch->academic_year_id,
            'semester_id' => $batch->semester_id,
            'class_id' => $batch->academic_class_id,
            'subject_assignment_id' => $batch->subject_assignment_id,
        ]);
        $rows = array_values(array_filter($preview['rows'] ?? [], static fn (array $row): bool => $row['valid'] && $row['score'] !== null));

        DB::transaction(function () use ($actor, $batch, $context, $rows): void {
            foreach ($rows as $row) {
                $this->grades->saveRow($actor, [
                    'academic_year_id' => $context['academicYear']->getKey(),
                    'semester_id' => $context['semester']->getKey(),
                    'class_id' => $context['academicClass']->getKey(),
                    'subject_id' => $context['assignment']->subject_id,
                    'subject_assignment_id' => $context['assignment']->getKey(),
                    'enrollment_id' => $row['enrollment_id'],
                    'score' => $row['score'],
                    'material' => $batch->material,
                    'description_snippet_id' => $this->previewSnippetId($batch),
                ]);
            }

            $batch->update([
                'status' => 'IMPORTED',
                'imported_rows' => count($rows),
                'imported_at' => now(),
            ]);
        });

        $this->audit->record(
            $actor,
            'grade_import.completed',
            GradeImportBatch::class,
            $batch->getKey(),
            null,
            ['status' => 'IMPORTED', 'imported_rows' => count($rows), 'file_name' => $batch->file_name],
        );

        return count($rows);
    }

    /** @param  array<string, mixed>  $preview */
    public function errorReportResponse(array $preview): StreamedResponse
    {
        return response()->streamDownload(function () use ($preview): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Baris', ...self::GRADE_HEADERS, 'Kesalahan']);
            foreach ($preview['errors'] ?? [] as $error) {
                fputcsv($output, [
                    $error['row'],
                    $error['values']['ENROLLMENT_ID'] ?? '',
                    $error['values']['NIPD'] ?? '',
                    $error['values']['NISN'] ?? '',
                    $error['values']['NAMA LENGKAP'] ?? '',
                    $error['values']['NILAI (0-100)'] ?? '',
                    '',
                    '',
                    '',
                    implode(' ', $error['errors'] ?? []),
                ]);
            }
            fclose($output);
        }, 'error-import-nilai.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param  array<string, mixed>  $filters */
    public function resolveContext(User $actor, array $filters): array
    {
        $required = ['academic_year_id', 'semester_id', 'class_id', 'subject_assignment_id'];
        foreach ($required as $key) {
            if (! isset($filters[$key]) || (int) $filters[$key] < 1) {
                throw new RuntimeException('Konteks tahun pelajaran, semester, kelas, dan mata pelajaran wajib dipilih.');
            }
        }

        $grid = $this->grades->grid($actor, $filters);
        $academicYear = $grid['academicYear'];
        $semester = $grid['semester'];
        $academicClass = $grid['academicClass'];
        $assignment = $grid['assignment'];
        if (! $academicYear || ! $semester || ! $academicClass || ! $assignment
            || $academicYear->getKey() !== (int) $filters['academic_year_id']
            || $semester->getKey() !== (int) $filters['semester_id']
            || $academicClass->getKey() !== (int) $filters['class_id']
            || $assignment->getKey() !== (int) $filters['subject_assignment_id']) {
            throw new RuntimeException('Konteks template tidak tersedia atau tidak sesuai dengan hak akses pengguna.');
        }
        if ($academicYear->status === AcademicYearStatus::Closed || $semester->status === SemesterStatus::Closed) {
            throw new RuntimeException('Tahun pelajaran atau semester sudah ditutup dan tidak menerima impor nilai.');
        }

        return compact('academicYear', 'semester', 'academicClass', 'assignment');
    }

    /** @return Collection<int, Enrollment> */
    private function enrollments(AcademicClass $class, AcademicYear $year): Collection
    {
        return Enrollment::query()
            ->where('class_id', $class->getKey())
            ->where('academic_year_id', $year->getKey())
            ->where('enrollment_status', 'ACTIVE')
            ->with('student')
            ->get()
            ->sortBy(fn (Enrollment $enrollment): string => mb_strtolower((string) $enrollment->student?->full_name))
            ->values();
    }

    /** @param  array<string, mixed>  $context */
    private function templateSheets(array $context): array
    {
        $enrollments = $this->enrollments($context['academicClass'], $context['academicYear']);
        $instructions = [
            ['BAGIAN', 'PETUNJUK IMPORT NILAI'],
            ['1', 'Gunakan file ini hanya untuk konteks tahun pelajaran, semester, kelas, dan mata pelajaran yang tercantum pada sheet KONTEKS.'],
            ['2', 'Kolom hijau adalah satu-satunya kolom yang boleh diisi: MATERI pada KONTEKS dan NILAI pada NILAI.'],
            ['3', 'Isi MATERI satu kali. Sistem akan membuat deskripsi otomatis berdasarkan predikat nilai masing-masing siswa.'],
            ['4', 'Jangan mengubah kolom identitas siswa, Enrollment ID, nama sheet, atau header. Server akan mencocokkan semuanya.'],
            ['5', 'PREDIKAT, DESKRIPSI OTOMATIS, dan STATUS adalah hasil server. Nilai yang ditulis pada kolom tersebut akan diabaikan.'],
            ['6', 'Nilai boleh dikosongkan untuk mempertahankan nilai yang sudah ada. Nilai baru harus bilangan bulat 0 sampai 100.'],
            ['7', 'Simpan sebagai XLSX, unggah, periksa preview, lalu konfirmasi. Tidak ada nilai yang disimpan langsung saat upload.'],
            ['8', 'Versi template: '.self::TEMPLATE_VERSION],
        ];

        $contextRows = [
            ['FIELD', 'VALUE', 'KETERANGAN'],
            ['TEMPLATE_VERSION', self::TEMPLATE_VERSION, 'Jangan diubah.'],
            ['ACADEMIC_YEAR_ID', (string) $context['academicYear']->getKey(), 'Konteks server.'],
            ['SEMESTER_ID', (string) $context['semester']->getKey(), 'Konteks server.'],
            ['CLASS_ID', (string) $context['academicClass']->getKey(), 'Konteks server.'],
            ['SUBJECT_ASSIGNMENT_ID', (string) $context['assignment']->getKey(), 'Konteks server.'],
            ['MATERI', '', 'Isi satu kali pada sel hijau. Contoh: memahami pengertian sifat terpuji.'],
            ['BANK_DESKRIPSI_ID', '', 'Opsional. Kosongkan bila memakai materi manual.'],
        ];

        $gradeRows = [self::GRADE_HEADERS];
        foreach ($enrollments as $index => $enrollment) {
            $gradeRows[] = [
                (string) ($index + 1),
                (string) $enrollment->getKey(),
                (string) $enrollment->student->student_number,
                (string) ($enrollment->student->national_student_number ?? ''),
                $enrollment->student->full_name,
                '',
                '—',
                'Akan dibuat server setelah preview',
                'BELUM DIIMPOR',
            ];
        }

        return [
            'PETUNJUK' => $instructions,
            'KONTEKS' => $contextRows,
            'NILAI' => $gradeRows,
        ];
    }

    /** @param  array<int, array<int, string>>  $rows */
    private function contextValues(array $rows): array
    {
        $values = [];
        foreach (array_slice($rows, 1) as $row) {
            $key = str_replace(' ', '_', $this->normalizeHeader($row[0] ?? ''));
            if ($key !== '') {
                $values[$key] = trim((string) ($row[1] ?? ''));
            }
        }

        return $values;
    }

    /** @param  array<string, mixed>  $context */
    private function assertWorkbookContext(array $context, array $values): void
    {
        $expected = [
            'template_version' => self::TEMPLATE_VERSION,
            'academic_year_id' => (string) $context['academicYear']->getKey(),
            'semester_id' => (string) $context['semester']->getKey(),
            'class_id' => (string) $context['academicClass']->getKey(),
            'subject_assignment_id' => (string) $context['assignment']->getKey(),
        ];
        foreach ($expected as $key => $value) {
            if (($values[$key] ?? '') !== $value) {
                throw new RuntimeException("Sheet KONTEKS tidak sesuai: {$key} harus {$value}.");
            }
        }
    }

    private function resolveSnippet(User $actor, SubjectAssignment $assignment, mixed $snippetId): ?DescriptionSnippet
    {
        if (trim((string) $snippetId) === '') {
            return null;
        }
        $snippets = $this->snippets->listUsableSnippets($actor, $assignment->subject);
        $snippet = $snippets->firstWhere('id', (int) $snippetId);
        if ($snippet === null) {
            throw new RuntimeException('BANK_DESKRIPSI_ID tidak tersedia atau tidak sesuai dengan mata pelajaran ini.');
        }

        return $snippet;
    }

    /** @param  array<int, string>  $headers */
    private function mapGradeHeaders(array $headers): array
    {
        $aliases = [
            'no' => 'no',
            'nomor' => 'no',
            'enrollment id' => 'enrollment_id',
            'id enrollment' => 'enrollment_id',
            'nipd' => 'nipd',
            'nisn' => 'nisn',
            'nama lengkap' => 'full_name',
            'nama' => 'full_name',
            'nilai 0 100' => 'score',
            'nilai (0 100)' => 'score',
            'nilai' => 'score',
            'predikat server' => 'predicate',
            'predikat' => 'predicate',
            'deskripsi otomatis server' => 'description',
            'deskripsi otomatis' => 'description',
            'status server' => 'status',
            'status' => 'status',
        ];
        $mapped = [];
        $unknown = [];
        foreach ($headers as $header) {
            $key = $this->normalizeHeader($header);
            if ($key === '') {
                continue;
            }
            $column = $aliases[$key] ?? null;
            if ($column === null) {
                $unknown[] = trim((string) $header);

                continue;
            }
            if (isset($mapped[$column])) {
                throw new RuntimeException('Header NILAI memiliki kolom duplikat: '.$header.'.');
            }
            $mapped[$column] = array_search($header, $headers, true);
        }
        $required = ['enrollment_id', 'nipd', 'nisn', 'full_name', 'score'];
        $missing = array_values(array_diff($required, array_keys($mapped)));
        if ($missing !== [] || $unknown !== []) {
            $details = [];
            if ($missing !== []) {
                $details[] = 'kolom wajib hilang: '.implode(', ', $missing);
            }
            if ($unknown !== []) {
                $details[] = 'kolom tidak dikenal: '.implode(', ', $unknown);
            }
            throw new RuntimeException('Header sheet NILAI tidak sesuai template ('.implode('; ', $details).').');
        }

        return $mapped;
    }

    /** @param  array<int, string>  $row @param  array<string, int>  $columns @return array<string, string> */
    private function rowValues(array $row, array $columns): array
    {
        $values = [];
        foreach ($columns as $key => $position) {
            $values[$key] = trim((string) ($row[$position] ?? ''));
        }

        return $values;
    }

    /** @param  array<int, string>  $row */
    private function blankRow(array $row): bool
    {
        return count(array_filter($row, static fn (mixed $value): bool => trim((string) $value) !== '')) === 0;
    }

    /** @param  array<int, string>  $errors */
    private function parseScore(string $value, array &$errors): ?int
    {
        if ($value === '') {
            return null;
        }
        if (! preg_match('/\A\d+\z/', $value)) {
            $errors[] = 'Nilai harus bilangan bulat 0 sampai 100.';

            return null;
        }
        $score = (int) $value;
        if ($score < 0 || $score > 100) {
            $errors[] = 'Nilai harus berada pada rentang 0 sampai 100.';

            return null;
        }

        return $score;
    }

    private function normalizeHeader(mixed $value): string
    {
        $value = str_replace(['–', '—', '_', '-', '(', ')'], ['-', '-', ' ', ' ', '', ''], (string) $value);

        return strtolower(trim((string) preg_replace('/\s+/', ' ', $value)));
    }

    private function assertSafeFile(UploadedFile $file): void
    {
        if (! $file->isValid() || $file->getSize() === false || $file->getSize() > 10 * 1024 * 1024) {
            throw new RuntimeException('File XLSX tidak valid atau melebihi batas 10 MB.');
        }
        $extension = strtolower((string) $file->extension());
        $mime = strtolower((string) $file->getMimeType());
        if ($extension !== 'xlsx' || ! in_array($mime, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'], true)) {
            throw new RuntimeException('Import nilai hanya menerima file XLSX dari template resmi.');
        }
    }

    private function previewSnippetId(GradeImportBatch $batch): ?int
    {
        $id = $batch->preview_json['description_snippet_id'] ?? null;

        return $id === null || $id === '' ? null : (int) $id;
    }
}
