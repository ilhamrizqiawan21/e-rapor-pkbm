<?php

namespace App\Services;

use App\Enums\AcademicYearStatus;
use App\Enums\SemesterStatus;
use App\Exceptions\GradeConcurrencyConflict;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\DescriptionSnippet;
use App\Models\Enrollment;
use App\Models\ReportCard;
use App\Models\ReportGrade;
use App\Models\Semester;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

/**
 * Coordinates grade-grid reads and writes while enforcing period, assignment,
 * workflow, and audit invariants.
 */
final class GradeEntryService
{
    public function __construct(
        private readonly ReportGradeSnapshotService $snapshots,
        private readonly DescriptionSnippetUsageService $snippets,
        private readonly GradeConcurrencyService $concurrency,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Build the filter options and the paginated student grid for grade entry.
     *
     * @return array<string, mixed>
     */
    public function grid(User $actor, array $filters): array
    {
        $this->assertCanView($actor);
        $institution = $actor->institution;
        $years = $this->academicYears($actor)->get();
        $academicYear = $this->selectAcademicYear($years, $filters['academic_year_id'] ?? null);
        $semesters = $academicYear?->semesters()->orderBy('sequence')->orderBy('id')->get() ?? new Collection;
        $semester = $this->selectSemester($semesters, $filters['semester_id'] ?? null);
        $classes = $academicYear ? $this->classes($actor, $academicYear)->get() : new Collection;
        $academicClass = $this->selectClass($classes, $filters['class_id'] ?? null);
        $assignments = $academicClass ? $this->assignments($actor, $academicClass)->get() : new Collection;
        $subjects = $assignments->pluck('subject')->filter()->unique('id')->sortBy('name')->values();
        $subjectId = isset($filters['subject_id']) ? (int) $filters['subject_id'] : null;
        $assignment = $this->selectAssignment($assignments, $subjectId, $filters['subject_assignment_id'] ?? null);

        $students = null;
        if ($academicClass && $semester && $assignment) {
            $students = $this->students(
                $academicClass,
                $semester,
                $assignment,
                (string) ($filters['search'] ?? ''),
                (int) ($filters['per_page'] ?? 25),
            );
        }

        return [
            'institution' => $institution,
            'academicYears' => $years,
            'semesters' => $semesters,
            'academicClasses' => $classes,
            'subjects' => $subjects,
            'assignments' => $assignments,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'academicClass' => $academicClass,
            'assignment' => $assignment,
            'students' => $students,
            'filters' => $filters,
        ];
    }

    /**
     * Save one student row. The report card is created lazily on first input.
     */
    public function saveRow(User $actor, array $data): ReportGrade
    {
        // Context is resolved from tenant-scoped relations before the row is
        // written, so submitted ids cannot redirect a grade to another class.
        $this->assertCanEdit($actor);
        $context = $this->contextForWrite($actor, $data);
        $this->validateRowInput($data);

        $score = $this->normaliseScore($data['score'] ?? null);
        $description = array_key_exists('description', $data) ? $data['description'] : null;
        $snippetId = $data['description_snippet_id'] ?? null;

        return DB::transaction(function () use ($actor, $context, $data, $score, $description, $snippetId): ReportGrade {
            /** @var Enrollment $enrollment */
            $enrollment = $context['enrollment'];
            /** @var Semester $semester */
            $semester = $context['semester'];
            /** @var SubjectAssignment $assignment */
            $assignment = $context['assignment'];

            $reportCard = ReportCard::query()->firstOrCreate([
                'enrollment_id' => $enrollment->getKey(),
                'semester_id' => $semester->getKey(),
            ], [
                'status' => 'DRAFT',
            ]);
            Gate::forUser($actor)->authorize('update', $reportCard);

            $grade = $reportCard->grades()->where('subject_assignment_id', $assignment->getKey())->first();
            $oldValues = $grade === null ? null : $this->auditValues($grade);

            if ($grade === null) {
                $grade = $this->snapshots->createSnapshot($reportCard, $assignment, $score);
            } elseif (($data['expected_updated_at'] ?? '') !== '') {
                $grade = $this->concurrency->updateScore($grade, $data['expected_updated_at'], $score);
            } else {
                $grade = $this->snapshots->updateScore($grade, $score);
            }

            if ($snippetId !== null && $snippetId !== '') {
                $snippet = $actor->ownedDescriptionSnippets()->whereKey($snippetId)->first()
                    ?? DescriptionSnippet::query()->whereKey($snippetId)->firstOrFail();
                $grade = $this->snippets->applySnippet($actor, $grade, $snippet);
            } elseif (array_key_exists('description', $data)) {
                $grade = $this->snippets->updateFinalDescription($actor, $grade, $description);
            }

            $freshGrade = $grade->fresh(['reportCard', 'subjectAssignment.subject', 'descriptionSnippet']);
            $this->audit->record(
                $actor,
                'report_grade.updated',
                ReportGrade::class,
                $freshGrade->getKey(),
                $oldValues,
                $this->auditValues($freshGrade),
            );

            return $freshGrade;
        });
    }

    /**
     * Save rows independently so a bad row cannot roll back valid rows.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{saved: int, errors: array<int, string>}
     */
    public function saveRows(User $actor, array $context, array $rows): array
    {
        $saved = 0;
        $errors = [];

        foreach ($rows as $row) {
            $enrollmentId = (int) ($row['enrollment_id'] ?? 0);

            try {
                $this->saveRow($actor, [...$context, ...$row]);
                $saved++;
            } catch (ValidationException|AuthorizationException|ModelNotFoundException|LogicException|InvalidArgumentException|GradeConcurrencyConflict $exception) {
                $errors[$enrollmentId] = $this->messageFor($exception);
            }
        }

        return ['saved' => $saved, 'errors' => $errors];
    }

    public function assertCanView(User $actor): void
    {
        if (! $actor->isActive() || $actor->institution_id === null || ! $actor->hasAnyRole(['ADMIN', 'GURU', 'KEPSEK'])) {
            throw new AuthorizationException('Pengguna tidak memiliki akses ke input nilai.');
        }
    }

    public function assertCanEdit(User $actor): void
    {
        $this->assertCanView($actor);

        if (! $actor->hasAnyRole(['ADMIN', 'GURU'])) {
            throw new AuthorizationException('Hanya Admin atau Guru yang dapat menyimpan nilai.');
        }
    }

    /** @return Builder<AcademicYear> */
    private function academicYears(User $actor): Builder
    {
        return AcademicYear::query()
            ->where('institution_id', $actor->institution_id)
            ->when($actor->hasRole('GURU'), fn (Builder $query) => $query->whereHas('classes.subjectAssignments', fn (Builder $assignments) => $assignments->where('teacher_id', $actor->getKey())->where('active', true)))
            ->orderByDesc('status')
            ->orderByDesc('start_date')
            ->orderByDesc('id');
    }

    /** @return HasMany<AcademicClass> */
    private function classes(User $actor, AcademicYear $academicYear): HasMany
    {
        return $academicYear->classes()
            ->where('status', 'ACTIVE')
            ->when($actor->hasRole('GURU'), fn (Builder $query) => $query->whereHas('subjectAssignments', fn (Builder $assignments) => $assignments->where('teacher_id', $actor->getKey())->where('active', true)))
            ->with(['program', 'level'])
            ->orderBy('name');
    }

    /** @return HasMany<SubjectAssignment> */
    private function assignments(User $actor, AcademicClass $academicClass): HasMany
    {
        return $academicClass->subjectAssignments()
            ->where('active', true)
            ->when($actor->hasRole('GURU'), fn (Builder $query) => $query->where('teacher_id', $actor->getKey()))
            ->with(['subject', 'teacher.staffProfile'])
            ->orderBy('display_order')
            ->orderBy('id');
    }

    private function selectAcademicYear(Collection $years, mixed $selectedId): ?AcademicYear
    {
        if ($years->isEmpty()) {
            return null;
        }

        return $years->firstWhere('id', (int) $selectedId)
            ?? $years->firstWhere('status', AcademicYearStatus::Active)
            ?? $years->first();
    }

    private function selectSemester(Collection $semesters, mixed $selectedId): ?Semester
    {
        if ($semesters->isEmpty()) {
            return null;
        }

        return $semesters->firstWhere('id', (int) $selectedId)
            ?? $semesters->firstWhere('status', SemesterStatus::Active)
            ?? $semesters->first();
    }

    private function selectClass(Collection $classes, mixed $selectedId): ?AcademicClass
    {
        return $classes->firstWhere('id', (int) $selectedId) ?? $classes->first();
    }

    private function selectAssignment(Collection $assignments, mixed $subjectId, mixed $assignmentId): ?SubjectAssignment
    {
        if ($assignmentId !== null) {
            return $assignments->firstWhere('id', (int) $assignmentId);
        }

        return $assignments->firstWhere('subject_id', (int) $subjectId) ?? $assignments->first();
    }

    private function students(
        AcademicClass $academicClass,
        Semester $semester,
        SubjectAssignment $assignment,
        string $search,
        int $perPage,
    ): LengthAwarePaginator {
        return Enrollment::query()
            ->where('class_id', $academicClass->getKey())
            ->where('academic_year_id', $academicClass->academic_year_id)
            ->where('enrollment_status', 'ACTIVE')
            ->with([
                'student',
                'reportCards' => fn ($query) => $query
                    ->where('semester_id', $semester->getKey())
                    ->with(['grades' => fn ($grades) => $grades->where('subject_assignment_id', $assignment->getKey())]),
            ])
            ->when(trim($search) !== '', function (Builder $query) use ($search): void {
                $term = '%'.trim($search).'%';
                $query->whereHas('student', fn (Builder $students) => $students
                    ->where('full_name', 'like', $term)
                    ->orWhere('student_number', 'like', $term)
                    ->orWhere('national_student_number', 'like', $term));
            })
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->select('enrollments.*')
            ->orderBy('students.full_name')
            ->orderBy('enrollments.id')
            ->paginate(max(1, min($perPage, 100)))
            ->withQueryString();
    }

    /** @return array<string, mixed> */
    private function contextForWrite(User $actor, array $data): array
    {
        if (! empty($data['subject_assignment_id'])) {
            $requestedAssignment = SubjectAssignment::query()
                ->with(['academicClass.program'])
                ->find($data['subject_assignment_id']);

            if ($requestedAssignment?->academicClass?->program?->institution_id === $actor->institution_id
                && $actor->hasRole('GURU')
                && $requestedAssignment->teacher_id !== $actor->getKey()) {
                throw new AuthorizationException('Guru hanya dapat mengisi assignment mata pelajaran miliknya.');
            }
        }

        $filters = [
            'academic_year_id' => $data['academic_year_id'] ?? null,
            'semester_id' => $data['semester_id'] ?? null,
            'class_id' => $data['class_id'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'subject_assignment_id' => $data['subject_assignment_id'] ?? null,
        ];
        $grid = $this->grid($actor, $filters);
        $year = $grid['academicYear'];
        $semester = $grid['semester'];
        $academicClass = $grid['academicClass'];
        $assignment = $grid['assignment'];

        if (! $year || ! $semester || ! $academicClass || ! $assignment) {
            throw new ModelNotFoundException('Konteks input nilai tidak ditemukan.');
        }

        $this->ensurePeriodOpen($year, $semester);
        $enrollment = Enrollment::query()
            ->with('student')
            ->whereKey($data['enrollment_id'] ?? 0)
            ->where('class_id', $academicClass->getKey())
            ->where('academic_year_id', $year->getKey())
            ->where('enrollment_status', 'ACTIVE')
            ->whereHas('student', fn (Builder $query) => $query->where('institution_id', $actor->institution_id))
            ->first();

        if (! $enrollment) {
            throw new ModelNotFoundException('Enrollment peserta didik tidak ditemukan.');
        }

        if ($actor->hasRole('GURU') && $assignment->teacher_id !== $actor->getKey()) {
            throw new AuthorizationException('Guru hanya dapat mengisi assignment mata pelajaran miliknya.');
        }

        return compact('year', 'semester', 'academicClass', 'assignment', 'enrollment');
    }

    private function ensurePeriodOpen(AcademicYear $year, Semester $semester): void
    {
        if ($year->status === AcademicYearStatus::Closed || $semester->status === SemesterStatus::Closed) {
            throw new LogicException('Tahun pelajaran atau semester sudah ditutup dan tidak menerima input nilai.');
        }
    }

    private function validateRowInput(array $data): void
    {
        Validator::make($data, [
            'score' => ['nullable', 'integer', 'between:0,100'],
            'description' => ['nullable', 'string', 'max:10000'],
            'description_snippet_id' => ['nullable', 'integer'],
            'expected_updated_at' => ['nullable', 'date'],
        ])->validate();
    }

    private function normaliseScore(mixed $score): ?int
    {
        return $score === null || $score === '' ? null : (int) $score;
    }

    private function messageFor(\Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return collect($exception->errors())->flatten()->first() ?? 'Data nilai tidak valid.';
        }

        return $exception->getMessage() !== '' ? $exception->getMessage() : 'Baris nilai tidak dapat disimpan.';
    }

    /** @return array<string, mixed> */
    private function auditValues(ReportGrade $grade): array
    {
        return [
            'report_card_id' => $grade->report_card_id,
            'subject_assignment_id' => $grade->subject_assignment_id,
            'score' => $grade->score,
            'predicate' => $grade->predicate,
            'description' => $grade->description,
            'status' => $grade->status,
        ];
    }
}
