<?php

namespace App\Services\Reports;

use App\Contracts\PdfEngine;
use App\Exceptions\LedgerExportException;
use App\Models\AcademicClass;
use App\Models\ReportCard;
use App\Models\ReportGrade;
use App\Models\Semester;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Builds ledger exports from printable report cards visible to the actor's
 * institution and teaching scope.
 */
final class LedgerExportService
{
    public const PRINTABLE_STATUSES = ['APPROVED', 'LOCKED'];

    public function __construct(
        private readonly PdfEngine $engine,
        private readonly NativeXlsxWriter $xlsx,
        private readonly AuditLogger $audit,
    ) {}

    /** @return array<string, mixed> */
    public function classData(User $actor, AcademicClass $academicClass, Semester $semester): array
    {
        Gate::forUser($actor)->authorize('view', $academicClass);
        $this->assertPeriod($actor, $academicClass, $semester);

        return $this->buildClass($academicClass, $semester);
    }

    /** @return array{classes: array<int, array<string, mixed>>, exported_at: string} */
    public function allData(User $actor, Semester $semester): array
    {
        abort_unless($actor->hasAnyRole(['ADMIN', 'GURU', 'KEPSEK']), 403);
        Gate::forUser($actor)->authorize('view', $semester);

        $classes = $this->permittedClasses($actor, $semester);
        if ($classes->isEmpty()) {
            throw new LedgerExportException('Tidak ada kelas yang dapat diekspor pada periode ini.');
        }

        return [
            'classes' => $classes->map(fn (AcademicClass $academicClass): array => $this->buildClass($academicClass, $semester))->values()->all(),
            'exported_at' => now()->format('d/m/Y H:i'),
        ];
    }

    /** @return array{path: string, filename: string, count: int} */
    public function excel(User $actor, Semester $semester, ?AcademicClass $academicClass = null): array
    {
        $payload = $academicClass === null
            ? $this->allData($actor, $semester)
            : ['classes' => [$this->classData($actor, $academicClass, $semester)], 'exported_at' => now()->format('d/m/Y H:i')];
        $path = tempnam(sys_get_temp_dir(), 'erapor-ledger-');

        if ($path === false) {
            throw new LedgerExportException('File Excel leger tidak dapat dibuat.');
        }

        try {
            $this->xlsx->write($this->xlsxSheets($payload), $path);
        } catch (\Throwable $exception) {
            @unlink($path);
            throw $exception;
        }

        $result = [
            'path' => $path,
            'filename' => $this->filename('xlsx', $semester, $academicClass),
            'count' => count($payload['classes']),
        ];
        $this->auditExport($actor, $semester, $academicClass, 'xlsx', $result['filename'], $result['count']);

        return $result;
    }

    /** @return array{contents: string, filename: string, count: int} */
    public function pdf(User $actor, Semester $semester, ?AcademicClass $academicClass = null): array
    {
        $payload = $academicClass === null
            ? $this->allData($actor, $semester)
            : ['classes' => [$this->classData($actor, $academicClass, $semester)], 'exported_at' => now()->format('d/m/Y H:i')];
        $html = view('reports.ledger.pdf', $payload)->render();

        $result = [
            'contents' => $this->engine->render($html),
            'filename' => $this->filename('pdf', $semester, $academicClass),
            'count' => count($payload['classes']),
        ];
        $this->auditExport($actor, $semester, $academicClass, 'pdf', $result['filename'], $result['count']);

        return $result;
    }

    /** @return Collection<int, AcademicClass> */
    private function permittedClasses(User $actor, Semester $semester): Collection
    {
        // The institution predicate is applied before the optional teacher
        // assignment filter to prevent cross-tenant class-id probing.
        $query = AcademicClass::query()
            ->with(['academicYear', 'program.institution', 'level', 'homeroomTeacher.staffProfile'])
            ->where('academic_year_id', $semester->academic_year_id)
            ->whereHas('program', fn (Builder $program): Builder => $program->where('institution_id', $actor->institution_id))
            ->orderBy('code')
            ->orderBy('name');

        if ($actor->hasRole('GURU')) {
            $query->where(function (Builder $class) use ($actor): void {
                $class
                    ->where('homeroom_teacher_id', $actor->getKey())
                    ->orWhereHas('subjectAssignments', fn (Builder $assignment): Builder => $assignment->where('teacher_id', $actor->getKey()));
            });
        }

        return $query->get();
    }

    private function assertPeriod(User $actor, AcademicClass $academicClass, Semester $semester): void
    {
        $academicClass->loadMissing(['academicYear', 'program']);
        $semester->loadMissing('academicYear');

        if ($academicClass->academic_year_id !== $semester->academic_year_id) {
            throw ValidationException::withMessages(['semester' => 'Kelas dan semester harus berada pada tahun pelajaran yang sama.']);
        }

        if ($academicClass->program?->institution_id !== $actor->institution_id
            || $semester->academicYear?->institution_id !== $actor->institution_id) {
            abort(403);
        }
    }

    /** @return array<string, mixed> */
    private function buildClass(AcademicClass $academicClass, Semester $semester): array
    {
        $academicClass->loadMissing([
            'academicYear',
            'program.institution.signatorySetting.headmasterUser',
            'level',
            'homeroomTeacher.staffProfile',
            'subjectAssignments.subject',
        ]);

        $enrollments = $academicClass->enrollments()
            ->where('academic_year_id', $semester->academic_year_id)
            ->with(['student', 'reportingEnrollment'])
            ->get()
            ->sortBy(fn ($enrollment): string => mb_strtolower((string) $enrollment->student?->full_name))
            ->values();

        $reportingIds = $enrollments
            ->map(fn ($enrollment): int => (int) ($enrollment->reporting_enrollment_id ?: $enrollment->getKey()))
            ->unique()
            ->values();

        $reportCards = ReportCard::query()
            ->where('semester_id', $semester->getKey())
            ->whereIn('enrollment_id', $reportingIds)
            ->whereIn('status', self::PRINTABLE_STATUSES)
            ->with([
                'enrollment.student',
                'grades.subjectAssignment.teacher.staffProfile',
                'attendanceSummary',
                'homeroomNote',
            ])
            ->get()
            ->keyBy('enrollment_id');

        $rows = [];
        foreach ($enrollments as $enrollment) {
            $reportingId = (int) ($enrollment->reporting_enrollment_id ?: $enrollment->getKey());
            $reportCard = $reportCards->get($reportingId);

            // A transfer may point to a reporting enrollment; verify the student
            // identity again before including a card in the exported ledger.
            if (! $reportCard instanceof ReportCard || $reportCard->enrollment?->student_id !== $enrollment->student_id) {
                continue;
            }

            $grades = $reportCard->grades
                ->sortBy(fn (ReportGrade $grade): array => [(int) $grade->display_order_snapshot, (int) $grade->getKey()])
                ->values();
            $scores = [];
            foreach ($grades as $grade) {
                $scores[$this->gradeKey($grade)] = $grade->score;
            }
            $numericScores = $grades->pluck('score')->filter(fn ($score): bool => is_numeric($score))->map(fn ($score): float => (float) $score);

            $rows[] = [
                'no' => count($rows) + 1,
                'student_number' => $enrollment->student?->student_number ?: '—',
                'nisn' => $enrollment->student?->national_student_number ?: '—',
                'student_name' => $enrollment->student?->full_name ?: '—',
                'scores' => $scores,
                'average' => $numericScores->isEmpty() ? null : round($numericScores->avg(), 2),
                'sick' => $reportCard->attendanceSummary?->sick_days,
                'leave' => $reportCard->attendanceSummary?->leave_days,
                'unexcused' => $reportCard->attendanceSummary?->unexcused_days,
                'note' => $reportCard->homeroomNote?->note ?: '—',
                'report_card_status' => $reportCard->status,
                'report_card_id' => $reportCard->getKey(),
                'grades' => $grades,
            ];
        }

        $subjectColumns = [];
        $subjectLabels = [];
        $appendSubject = function (string $key, string $base, string $name) use (&$subjectColumns, &$subjectLabels): void {
            if (isset($subjectColumns[$key])) {
                return;
            }

            $base = $base !== '' ? mb_substr($base, 0, 25) : 'MAPEL';
            $label = $base;
            $suffix = 2;
            while (in_array($label, $subjectLabels, true)) {
                $label = mb_substr($base, 0, 21).' '.($suffix++);
            }
            $subjectLabels[] = $label;
            $subjectColumns[$key] = ['key' => $key, 'label' => $label, 'name' => $name !== '' ? $name : $base];
        };

        // Start with the class curriculum so a configured subject remains in
        // the ledger even when every report card is still missing its grade.
        $academicClass->subjectAssignments
            ->filter(fn ($assignment): bool => (bool) $assignment->active)
            ->sortBy(fn ($assignment): array => [(int) $assignment->display_order, (int) $assignment->getKey()])
            ->each(function ($assignment) use ($appendSubject): void {
                $name = trim((string) ($assignment->subject?->name ?? ''));
                $shortName = trim((string) ($assignment->subject?->short_name ?: $name));
                $appendSubject('assignment:'.$assignment->getKey(), $shortName, $name);
            });

        // A transferred report may contain an assignment from its reporting
        // class. Preserve those columns after the target class curriculum.
        collect($rows)
            ->flatMap(fn (array $row): Collection => $row['grades'])
            ->sortBy(fn (ReportGrade $grade): array => [(int) $grade->display_order_snapshot, (int) $grade->getKey()])
            ->each(function (ReportGrade $grade) use ($appendSubject): void {
                $appendSubject(
                    $this->gradeKey($grade),
                    trim((string) ($grade->subject_short_name_snapshot ?: $grade->subject_name_snapshot)),
                    trim((string) $grade->subject_name_snapshot),
                );
            });
        $subjectColumns = array_values($subjectColumns);
        $emptyScoreCount = collect($rows)->sum(function (array $row) use ($subjectColumns): int {
            return collect($subjectColumns)->filter(fn (array $subject): bool => ! is_numeric($row['scores'][$subject['key']] ?? null))->count();
        });

        $details = collect($rows)
            ->flatMap(fn (array $row): Collection => $row['grades']->map(fn (ReportGrade $grade): array => [
                'student_number' => $row['student_number'],
                'student_name' => $row['student_name'],
                'subject' => $grade->subject_name_snapshot ?: '—',
                'kkm' => $grade->minimum_score_snapshot,
                'score' => $grade->score,
                'predicate' => $grade->predicate ?: '—',
                'status' => $grade->status ?: '—',
                'teacher' => $grade->subjectAssignment?->teacher?->name ?: '—',
                'description' => $grade->description ?: '—',
                'period' => $semester->name.' / '.$semester->academicYear?->name,
            ]))
            ->values()
            ->all();

        $institution = $academicClass->program?->institution;
        $homeroom = $academicClass->homeroomTeacher;
        $headmaster = $institution?->signatorySetting?->headmaster_name
            ?: $institution?->signatorySetting?->headmasterUser?->name;

        return [
            'institution' => [
                'name' => $institution?->name ?: '—',
                'address' => $institution?->address ?: '—',
                'phone' => $institution?->phone ?: '—',
                'npsn' => $institution?->npsn ?: '—',
            ],
            'program' => $academicClass->program?->name ?: '—',
            'class' => $academicClass->name ?: $academicClass->code ?: '—',
            'class_code' => $academicClass->code ?: 'kelas',
            'semester' => $semester->name ?: ($semester->code?->value ?? $semester->code ?? '—'),
            'academic_year' => $semester->academicYear?->name ?: $academicClass->academicYear?->name ?: '—',
            'homeroom' => $homeroom?->name ?: '—',
            'homeroom_employee_number' => $homeroom?->staffProfile?->employee_number ?: '—',
            'headmaster' => $headmaster ?: '—',
            'subjects' => $subjectColumns,
            'rows' => $rows,
            'details' => $details,
            'empty_score_count' => $emptyScoreCount,
            'report_card_count' => count($rows),
            'enrollment_count' => $enrollments->count(),
            'exported_at' => now()->format('d/m/Y H:i'),
        ];
    }

    /** @param array{classes: array<int, array<string, mixed>>, exported_at: string} $payload */
    private function xlsxSheets(array $payload): array
    {
        $sheets = [[
            'name' => 'Summary',
            'freeze_row' => 4,
            'widths' => [18, 28, 18, 16, 16],
            'rows' => [
                [$this->cell('Leger Nilai PKBM', 2)],
                [$this->cell('Diekspor pada '.$payload['exported_at'], 0)],
                [],
                [$this->cell('Kelas', 1), $this->cell('Program', 1), $this->cell('Peserta didik', 1), $this->cell('Rapor resmi', 1), $this->cell('Nilai kosong', 1)],
            ],
        ]];

        $usedSheetNames = ['Summary'];
        foreach ($payload['classes'] as $class) {
            $sheets[0]['rows'][] = [
                $this->cell($class['class'], 4),
                $this->cell($class['program'], 4),
                $this->cell($class['enrollment_count'], 5),
                $this->cell($class['report_card_count'], 5),
                $this->cell($class['empty_score_count'], 5),
            ];
            $mainName = $this->uniqueSheetName('Leger '.$class['class'], $usedSheetNames);
            $usedSheetNames[] = $mainName;
            $detailName = $this->uniqueSheetName('Detail '.$class['class'], $usedSheetNames);
            $usedSheetNames[] = $detailName;
            $sheets[] = $this->mainSheet($class, $mainName);
            $sheets[] = $this->detailSheet($class, $detailName);
        }

        return $sheets;
    }

    /** @return array{name: string, rows: array<int, array<int, array{value: mixed, style?: int}>>, widths: array<int, float>, freeze_row: int} */
    private function mainSheet(array $data, string $name): array
    {
        $header = [
            $this->cell('No.', 1),
            $this->cell('NIPD', 1),
            $this->cell('NISN', 1),
            $this->cell('Nama Peserta Didik', 1),
        ];
        foreach ($data['subjects'] as $subject) {
            $header[] = $this->cell($subject['label'], 1);
        }
        $header = [...$header, $this->cell('Rata-rata', 1), $this->cell('Sakit', 1), $this->cell('Izin', 1), $this->cell('Tanpa Keterangan', 1), $this->cell('Keterangan', 1)];
        $rows = [
            [$this->cell('LEGER NILAI — '.$data['class'], 2)],
            [$this->cell('PKBM', 3), $this->cell($data['institution']['name'], 4)],
            [$this->cell('Program', 3), $this->cell($data['program'], 4), $this->cell('Tahun Pelajaran', 3), $this->cell($data['academic_year'], 4)],
            [$this->cell('Semester', 3), $this->cell($data['semester'], 4), $this->cell('Wali Kelas', 3), $this->cell($data['homeroom'], 4)],
            [$this->cell('Footer cetak', 3), $this->cell('Dicetak pada '.$data['exported_at'], 4)],
            [],
            $header,
        ];
        foreach ($data['rows'] as $row) {
            $values = [$this->cell($row['no'], 5), $this->cell($row['student_number'], 4), $this->cell($row['nisn'], 4), $this->cell($row['student_name'], 4)];
            foreach ($data['subjects'] as $subject) {
                $values[] = $this->cell($row['scores'][$subject['key']] ?? '—', is_numeric($row['scores'][$subject['key']] ?? null) ? 5 : 5);
            }
            $values[] = $this->cell($row['average'] ?? '—', $row['average'] === null ? 5 : 7);
            $values[] = $this->cell($row['sick'] ?? '—', 5);
            $values[] = $this->cell($row['leave'] ?? '—', 5);
            $values[] = $this->cell($row['unexcused'] ?? '—', 5);
            $values[] = $this->cell($row['note'], 4);
            $rows[] = $values;
        }
        $rows[] = [];
        $rows[] = [$this->cell('Tanda tangan wali kelas', 3), $this->cell($data['homeroom'], 4), $this->cell('Kepala PKBM', 3), $this->cell($data['headmaster'], 4)];

        return ['name' => $name, 'rows' => $rows, 'widths' => [7, 16, 16, 28, ...array_fill(0, count($data['subjects']), 12), 12, 10, 10, 16, 32], 'freeze_row' => 7];
    }

    /** @return array{name: string, rows: array<int, array<int, array{value: mixed, style?: int}>>, widths: array<int, float>, freeze_row: int} */
    private function detailSheet(array $data, string $name): array
    {
        $rows = [
            [$this->cell('DETAIL NILAI — '.$data['class'], 2)],
            [$this->cell('Periode', 3), $this->cell($data['semester'].' / '.$data['academic_year'], 4)],
            [],
            [$this->cell('NIPD', 1), $this->cell('Nama Peserta Didik', 1), $this->cell('Mata Pelajaran', 1), $this->cell('KKM', 1), $this->cell('Nilai', 1), $this->cell('Predikat', 1), $this->cell('Status Nilai', 1), $this->cell('Guru Pengampu', 1), $this->cell('Deskripsi', 1), $this->cell('Periode', 1)],
        ];
        foreach ($data['details'] as $detail) {
            $rows[] = [
                $this->cell($detail['student_number'], 4), $this->cell($detail['student_name'], 4), $this->cell($detail['subject'], 4), $this->cell($detail['kkm'] ?? '—', 5), $this->cell($detail['score'] ?? '—', 5), $this->cell($detail['predicate'], 5), $this->cell($detail['status'], 5), $this->cell($detail['teacher'], 4), $this->cell($detail['description'], 4), $this->cell($detail['period'], 4),
            ];
        }

        return ['name' => $name, 'rows' => $rows, 'widths' => [16, 28, 26, 9, 9, 10, 14, 24, 52, 24], 'freeze_row' => 3];
    }

    /** @return array{value: mixed, style: int} */
    private function cell(mixed $value, int $style): array
    {
        return ['value' => $value, 'style' => $style];
    }

    private function gradeKey(ReportGrade $grade): string
    {
        return 'assignment:'.($grade->subject_assignment_id ?: 'grade-'.$grade->getKey());
    }

    private function sheetName(string $value): string
    {
        $name = str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $value);
        $name = trim($name, " '\"");

        return mb_substr($name !== '' ? $name : 'Sheet', 0, 31);
    }

    /** @param array<int, string> $used */
    private function uniqueSheetName(string $value, array $used): string
    {
        $base = $this->sheetName($value);
        $candidate = $base;
        $suffix = 2;

        while (in_array($candidate, $used, true)) {
            $tail = ' '.($suffix++);
            $candidate = mb_substr($base, 0, 31 - mb_strlen($tail)).$tail;
        }

        return $candidate;
    }

    private function filename(string $extension, Semester $semester, ?AcademicClass $academicClass): string
    {
        $semester->loadMissing('academicYear');
        $year = Str::slug(str_replace('/', '-', (string) ($semester->academicYear?->name ?: 'tahun-pelajaran')));
        $term = Str::slug((string) ($semester->code?->value ?? $semester->code ?? $semester->name ?? 'semester'));
        $scope = $academicClass === null ? 'semua-kelas' : Str::slug((string) ($academicClass->code ?: $academicClass->name));

        return 'leger-'.$year.'-'.$term.'-'.$scope.'.'.$extension;
    }

    private function auditExport(User $actor, Semester $semester, ?AcademicClass $academicClass, string $format, string $filename, int $count): void
    {
        $this->audit->record($actor, 'ledger.exported', 'ledger', $academicClass?->getKey() ?? 0, null, [
            'format' => $format,
            'filename' => $filename,
            'semester_id' => $semester->getKey(),
            'academic_class_id' => $academicClass?->getKey(),
            'class_count' => $count,
        ]);
    }
}
