<?php

namespace App\Services;

use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicDecision;
use App\Models\AcademicDecisionBatch;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassTransfer;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\ReportCard;
use App\Models\ReportGrade;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds role-specific dashboard projections from authorized tenant context;
 * it does not grant access to records by itself.
 */
final class DashboardService
{
    public function __construct(
        private readonly AcademicPeriodContext $periodContext,
        private readonly ReportCardCompletenessService $completeness,
    ) {}

    /** @return array<string, mixed> */
    public function for(User $user, ?string $requestedRole = null): array
    {
        $user->loadMissing('roles');
        $role = $this->resolveRole($user, $requestedRole);
        $period = $this->periodContext->forInstitution($user->institution_id);

        return match ($role) {
            RoleCode::Admin->value => $this->admin($user, $period),
            RoleCode::Guru->value => $this->guru($user, $period),
            RoleCode::Kepsek->value => $this->kepsek($user, $period),
            default => $this->emptyDashboard($period),
        };
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function admin(User $user, array $period): array
    {
        $year = $period['academicYear'];
        $semester = $period['semester'];
        $hasPeriod = $year !== null && $semester !== null;
        $institutionId = $user->institution_id;
        $classCount = $hasPeriod ? AcademicClass::query()->where('academic_year_id', $year->getKey())->where('status', 'ACTIVE')->count() : 0;
        $studentCount = $hasPeriod ? Enrollment::query()
            ->where('academic_year_id', $year->getKey())
            ->where('enrollment_status', 'ACTIVE')
            ->whereHas('academicClass', fn (Builder $query) => $query->where('status', 'ACTIVE')->whereHas('program', fn (Builder $program) => $program->where('institution_id', $institutionId)))
            ->distinct('student_id')->count('student_id') : 0;
        $assignmentCount = $hasPeriod ? SubjectAssignment::query()
            ->where('active', true)
            ->whereHas('academicClass', fn (Builder $query) => $query->where('academic_year_id', $year->getKey())->whereHas('program', fn (Builder $program) => $program->where('institution_id', $institutionId)))
            ->count() : 0;
        $teacherCount = User::query()->where('institution_id', $institutionId)->where('status', 'ACTIVE')->whereHas('roles', fn (Builder $query) => $query->where('code', RoleCode::Guru->value))->count();
        $statusCounts = $this->reportStatusCounts($user, $period);
        $setupSteps = [
            [
                'key' => 'period',
                'label' => 'Aktifkan periode',
                'detail' => $hasPeriod ? $period['label'] : 'Tahun pelajaran dan semester belum aktif.',
                'done' => $hasPeriod,
                'route' => 'admin.academic-years.index',
                'action' => 'Kelola periode',
            ],
            [
                'key' => 'classes',
                'label' => 'Siapkan kelas / rombel',
                'detail' => $hasPeriod ? $classCount.' kelas aktif' : 'Menunggu periode aktif.',
                'done' => $hasPeriod && $classCount > 0,
                'route' => 'admin.academic-classes.index',
                'action' => 'Kelola kelas',
            ],
            [
                'key' => 'students',
                'label' => 'Masukkan peserta didik',
                'detail' => $hasPeriod ? $studentCount.' peserta didik aktif' : 'Menunggu kelas aktif.',
                'done' => $hasPeriod && $studentCount > 0,
                'route' => 'admin.students.index',
                'action' => 'Kelola peserta didik',
            ],
            [
                'key' => 'assignments',
                'label' => 'Petakan mapel dan guru',
                'detail' => $hasPeriod ? $assignmentCount.' penugasan aktif' : 'Menunggu peserta didik dan kelas.',
                'done' => $hasPeriod && $assignmentCount > 0,
                'route' => 'admin.academic-classes.subject-assignments.bulk-create',
                'action' => 'Petakan mapel',
            ],
        ];

        return [
            'role' => RoleCode::Admin->value,
            'roleLabel' => RoleCode::Admin->label(),
            'period' => $period,
            'hasPeriod' => $hasPeriod,
            'stats' => [
                ['label' => 'Kelas aktif', 'value' => $classCount, 'detail' => 'Pada periode aktif', 'periodScoped' => true],
                ['label' => 'Peserta didik', 'value' => $studentCount, 'detail' => 'Penempatan aktif', 'periodScoped' => true],
                ['label' => 'Guru aktif', 'value' => $teacherCount, 'detail' => 'Dalam institusi', 'periodScoped' => false],
                ['label' => 'Rapor siap dicetak', 'value' => $statusCounts['approved'], 'detail' => 'Data lengkap dan siap diterbitkan', 'periodScoped' => true],
            ],
            'setupSteps' => $setupSteps,
            'setupCompleted' => collect($setupSteps)->where('done', true)->count(),
            'statusCounts' => $statusCounts,
            'progressByClass' => $hasPeriod ? $this->adminClassProgress($user, $period) : collect(),
            'recentTransfers' => $hasPeriod ? $this->recentTransfers($user, $period) : collect(),
            'quickLinks' => [
                ['label' => 'Input nilai', 'route' => 'admin.grade-entry.index', 'variant' => 'primary'],
                ['label' => 'Kelola rapor', 'route' => 'admin.report-cards.index', 'variant' => 'secondary'],
                ['label' => 'Data peserta didik', 'route' => 'admin.students.index', 'variant' => 'secondary'],
                ['label' => 'Tahun pelajaran', 'route' => 'admin.academic-years.index', 'variant' => 'secondary'],
            ],
        ];
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function guru(User $user, array $period): array
    {
        $hasPeriod = $period['academicYear'] !== null && $period['semester'] !== null;
        $assignments = $hasPeriod ? $this->teacherAssignments($user, $period) : collect();
        $reportStatus = $hasPeriod ? $this->teacherReportStatus($user, $period) : ['drafts' => 0, 'revisions' => 0];
        $homeroomClasses = $hasPeriod ? $this->homeroomClasses($user, $period) : collect();

        return [
            'role' => RoleCode::Guru->value,
            'roleLabel' => RoleCode::Guru->label(),
            'period' => $period,
            'hasPeriod' => $hasPeriod,
            'stats' => [
                ['label' => 'Penugasan mapel', 'value' => $assignments->count(), 'detail' => 'Kelas dan mapel aktif'],
                ['label' => 'Belum lengkap', 'value' => $reportStatus['drafts'], 'detail' => 'Masih ada komponen rapor yang harus diisi'],
                ['label' => 'Perlu diperbarui', 'value' => $reportStatus['revisions'], 'detail' => 'Periksa kembali data rapor'],
                ['label' => 'Wali kelas', 'value' => $homeroomClasses->count(), 'detail' => 'Kelas aktif'],
            ],
            'assignments' => $assignments,
            'homeroomClasses' => $homeroomClasses,
            'descriptionBankRoute' => 'admin.description-snippets.index',
        ];
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function kepsek(User $user, array $period): array
    {
        $hasPeriod = $period['academicYear'] !== null && $period['semester'] !== null;
        $statusCounts = $this->reportStatusCounts($user, $period);
        $classSummary = $hasPeriod ? $this->kepsekClassSummary($user, $period) : collect();
        $institutionMetrics = $this->kepsekInstitutionMetrics($user, $period, $statusCounts);

        return [
            'role' => RoleCode::Kepsek->value,
            'roleLabel' => RoleCode::Kepsek->label(),
            'period' => $period,
            'hasPeriod' => $hasPeriod,
            'stats' => [
                ['label' => 'Rapor belum siap dicetak', 'value' => $statusCounts['draft'] + $statusCounts['submitted'] + $statusCounts['in_review'] + $statusCounts['revisions'], 'detail' => 'Masih perlu dilengkapi'],
                ['label' => 'Rapor siap dicetak', 'value' => $statusCounts['approved'], 'detail' => 'Dapat diterbitkan wali kelas'],
                ['label' => 'Rapor belum lengkap', 'value' => $statusCounts['revisions'], 'detail' => 'Perlu diperbarui'],
                ['label' => 'Terkunci', 'value' => $statusCounts['locked'], 'detail' => 'Arsip final'],
            ],
            'statusCounts' => $statusCounts,
            'institutionMetrics' => $institutionMetrics,
            'componentSummary' => $hasPeriod ? $this->kepsekComponentSummary($user, $period) : collect(),
            'classSummary' => $classSummary,
            'teacherProgress' => $hasPeriod ? $this->kepsekTeacherProgress($user, $period) : collect(),
            'subjectProgress' => $hasPeriod ? $this->kepsekSubjectProgress($user, $period) : collect(),
            'decisionSummary' => $hasPeriod ? $this->kepsekDecisionSummary($user, $period) : $this->emptyDecisionSummary(),
            'recentAudit' => $this->recentAudit($user),
            'reviewQueue' => $hasPeriod ? $this->reviewQueue($user, $period) : collect(),
        ];
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function kepsekInstitutionMetrics(User $user, array $period, array $statusCounts): array
    {
        $institutionId = $user->institution_id;
        $year = $period['academicYear'];
        $hasPeriod = $year !== null && $period['semester'] !== null;
        $classCount = $hasPeriod
            ? AcademicClass::query()
                ->where('academic_year_id', $year->getKey())
                ->where('status', 'ACTIVE')
                ->whereHas('program', fn (Builder $query) => $query->where('institution_id', $institutionId))
                ->count()
            : 0;
        $studentCount = $hasPeriod
            ? Enrollment::query()
                ->where('academic_year_id', $year->getKey())
                ->where('enrollment_status', 'ACTIVE')
                ->whereHas('academicClass', fn (Builder $query) => $query
                    ->where('status', 'ACTIVE')
                    ->whereHas('program', fn (Builder $program) => $program->where('institution_id', $institutionId)))
                ->distinct('student_id')
                ->count('student_id')
            : 0;
        $assignmentCount = $hasPeriod
            ? SubjectAssignment::query()
                ->where('active', true)
                ->whereHas('academicClass', fn (Builder $query) => $query
                    ->where('academic_year_id', $year->getKey())
                    ->whereHas('program', fn (Builder $program) => $program->where('institution_id', $institutionId)))
                ->count()
            : 0;
        $teacherCount = User::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'ACTIVE')
            ->whereHas('roles', fn (Builder $query) => $query->where('code', RoleCode::Guru->value))
            ->count();
        $subjectCount = Subject::query()->where('institution_id', $institutionId)->where('active', true)->count();
        $programCount = Program::query()->where('institution_id', $institutionId)->count();
        $reportCardCount = $statusCounts['waiting_review'] + $statusCounts['approved'] + $statusCounts['locked'];
        $coveragePercent = $studentCount > 0 ? (int) round(($reportCardCount / $studentCount) * 100) : 0;

        return [
            ['key' => 'classes', 'label' => 'Kelas aktif', 'value' => $classCount, 'detail' => 'Pada periode aktif'],
            ['key' => 'students', 'label' => 'Peserta didik', 'value' => $studentCount, 'detail' => 'Penempatan aktif'],
            ['key' => 'teachers', 'label' => 'Guru aktif', 'value' => $teacherCount, 'detail' => 'Dalam institusi'],
            ['key' => 'assignments', 'label' => 'Penugasan mapel', 'value' => $assignmentCount, 'detail' => 'Penugasan aktif pada periode'],
            ['key' => 'subjects', 'label' => 'Mata pelajaran', 'value' => $subjectCount, 'detail' => 'Mapel aktif dalam institusi'],
            ['key' => 'programs', 'label' => 'Program pendidikan', 'value' => $programCount, 'detail' => 'Program PKBM'],
            ['key' => 'report_coverage', 'label' => 'Cakupan rapor', 'value' => $coveragePercent, 'suffix' => '%', 'detail' => sprintf('%d dari %d peserta memiliki rapor', $reportCardCount, $studentCount)],
        ];
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function kepsekComponentSummary(User $user, array $period): Collection
    {
        $cards = $this->reportCards($user, $period)
            ->with(['enrollment.academicClass.subjectAssignments', 'grades', 'semester.academicYear'])
            ->get();
        $summary = [];

        foreach ($cards as $card) {
            foreach ($this->completeness->summary($card)['components'] as $component) {
                $key = $component['key'];
                $summary[$key] ??= [
                    'key' => $key,
                    'label' => $component['label'],
                    'total' => $cards->count(),
                    'complete' => 0,
                    'incomplete' => 0,
                    'percentage' => 0,
                ];
                if ($component['complete']) {
                    $summary[$key]['complete']++;
                } else {
                    $summary[$key]['incomplete']++;
                }
            }
        }

        foreach ($summary as &$component) {
            $component['percentage'] = $component['total'] > 0
                ? (int) round(($component['complete'] / $component['total']) * 100)
                : 0;
        }
        unset($component);

        return collect(array_values($summary));
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function kepsekTeacherProgress(User $user, array $period): Collection
    {
        $year = $period['academicYear'];
        $semester = $period['semester'];
        if ($year === null || $semester === null) {
            return collect();
        }

        $assignments = SubjectAssignment::query()
            ->with(['teacher', 'subject', 'academicClass'])
            ->where('active', true)
            ->whereHas('academicClass', fn (Builder $query) => $query
                ->where('academic_year_id', $year->getKey())
                ->whereHas('program', fn (Builder $program) => $program->where('institution_id', $user->institution_id)))
            ->orderBy('teacher_id')
            ->orderBy('display_order')
            ->get();
        $students = Enrollment::query()
            ->where('academic_year_id', $year->getKey())
            ->where('enrollment_status', 'ACTIVE')
            ->whereIn('class_id', $assignments->pluck('class_id')->unique())
            ->selectRaw('class_id, count(*) as student_count')
            ->groupBy('class_id')
            ->pluck('student_count', 'class_id');
        $filled = ReportGrade::query()
            ->join('report_cards', 'report_cards.id', '=', 'report_grades.report_card_id')
            ->where('report_cards.semester_id', $semester->getKey())
            ->whereNotNull('report_grades.score')
            ->whereNotNull('report_grades.description')
            ->where('report_grades.description', '<>', '')
            ->selectRaw('report_grades.subject_assignment_id, count(report_grades.id) as filled_count')
            ->groupBy('report_grades.subject_assignment_id')
            ->pluck('filled_count', 'subject_assignment_id');

        return $assignments->groupBy('teacher_id')->map(function (Collection $items, mixed $teacherId) use ($students, $filled): array {
            $expected = $items->sum(fn (SubjectAssignment $assignment): int => (int) ($students[$assignment->class_id] ?? 0));
            $filledCount = $items->sum(fn (SubjectAssignment $assignment): int => (int) ($filled[$assignment->getKey()] ?? 0));

            return [
                'teacher_id' => $teacherId !== null ? (int) $teacherId : null,
                'teacher_name' => $items->first()?->teacher?->name ?? 'Guru belum dipetakan',
                'assignment_count' => $items->count(),
                'class_count' => $items->pluck('class_id')->unique()->count(),
                'expected' => $expected,
                'filled' => $filledCount,
                'pending' => max(0, $expected - $filledCount),
                'progress_percent' => $expected > 0 ? (int) round(($filledCount / $expected) * 100) : 0,
            ];
        })->sortBy('progress_percent')->values();
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function kepsekSubjectProgress(User $user, array $period): Collection
    {
        $year = $period['academicYear'];
        $semester = $period['semester'];
        if ($year === null || $semester === null) {
            return collect();
        }

        $assignments = SubjectAssignment::query()
            ->with('subject')
            ->where('active', true)
            ->whereHas('academicClass', fn (Builder $query) => $query
                ->where('academic_year_id', $year->getKey())
                ->whereHas('program', fn (Builder $program) => $program->where('institution_id', $user->institution_id)))
            ->get();
        $students = Enrollment::query()
            ->where('academic_year_id', $year->getKey())
            ->where('enrollment_status', 'ACTIVE')
            ->whereIn('class_id', $assignments->pluck('class_id')->unique())
            ->selectRaw('class_id, count(*) as student_count')
            ->groupBy('class_id')
            ->pluck('student_count', 'class_id');
        $filled = ReportGrade::query()
            ->join('report_cards', 'report_cards.id', '=', 'report_grades.report_card_id')
            ->where('report_cards.semester_id', $semester->getKey())
            ->whereNotNull('report_grades.score')
            ->whereNotNull('report_grades.description')
            ->where('report_grades.description', '<>', '')
            ->selectRaw('report_grades.subject_assignment_id, count(report_grades.id) as filled_count')
            ->groupBy('report_grades.subject_assignment_id')
            ->pluck('filled_count', 'subject_assignment_id');

        return $assignments->groupBy('subject_id')->map(function (Collection $items, mixed $subjectId) use ($students, $filled): array {
            $expected = $items->sum(fn (SubjectAssignment $assignment): int => (int) ($students[$assignment->class_id] ?? 0));
            $filledCount = $items->sum(fn (SubjectAssignment $assignment): int => (int) ($filled[$assignment->getKey()] ?? 0));

            return [
                'subject_id' => $subjectId !== null ? (int) $subjectId : null,
                'subject_name' => $items->first()?->subject?->name ?? 'Mapel belum dipetakan',
                'assignment_count' => $items->count(),
                'teacher_count' => $items->pluck('teacher_id')->filter()->unique()->count(),
                'class_count' => $items->pluck('class_id')->unique()->count(),
                'expected' => $expected,
                'filled' => $filledCount,
                'pending' => max(0, $expected - $filledCount),
                'progress_percent' => $expected > 0 ? (int) round(($filledCount / $expected) * 100) : 0,
            ];
        })->sortBy('progress_percent')->values();
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function kepsekDecisionSummary(User $user, array $period): array
    {
        $year = $period['academicYear'];
        if ($year === null) {
            return $this->emptyDecisionSummary();
        }

        $classes = AcademicClass::query()
            ->where('academic_year_id', $year->getKey())
            ->where('status', 'ACTIVE')
            ->whereHas('program', fn (Builder $query) => $query->where('institution_id', $user->institution_id))
            ->get(['id']);
        $classIds = $classes->pluck('id');
        $studentIds = Enrollment::query()
            ->whereIn('class_id', $classIds)
            ->where('academic_year_id', $year->getKey())
            ->where('enrollment_status', 'ACTIVE')
            ->pluck('student_id')
            ->unique();
        $decisions = AcademicDecision::query()
            ->where('academic_year_id', $year->getKey())
            ->whereIn('class_id', $classIds)
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'decision']);
        $decided = $decisions->where('decision', '!=', 'UNDECIDED')->pluck('student_id')->unique()->count();
        $batches = AcademicDecisionBatch::query()->where('academic_year_id', $year->getKey())->whereIn('class_id', $classIds)->get(['status']);
        $totalStudents = $studentIds->count();

        return [
            'total_students' => $totalStudents,
            'decided' => $decided,
            'pending' => max(0, $totalStudents - $decided),
            'promote' => $decisions->where('decision', 'PROMOTE')->count(),
            'repeat' => $decisions->where('decision', 'REPEAT')->count(),
            'graduate' => $decisions->where('decision', 'GRADUATE')->count(),
            'not_graduated' => $decisions->where('decision', 'NOT_GRADUATED')->count(),
            'classes_total' => $classIds->count(),
            'classes_finalized' => $batches->where('status', 'FINALIZED')->count(),
            'classes_pending' => max(0, $classIds->count() - $batches->where('status', 'FINALIZED')->count()),
        ];
    }

    /** @return array<string, int> */
    private function emptyDecisionSummary(): array
    {
        return [
            'total_students' => 0,
            'decided' => 0,
            'pending' => 0,
            'promote' => 0,
            'repeat' => 0,
            'graduate' => 0,
            'not_graduated' => 0,
            'classes_total' => 0,
            'classes_finalized' => 0,
            'classes_pending' => 0,
        ];
    }

    private function recentAudit(User $user): Collection
    {
        return AuditLog::query()
            ->where('institution_id', $user->institution_id)
            ->with('user')
            ->latest('created_at')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'id' => $log->getKey(),
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'actor' => $log->user?->name ?? 'Sistem',
                'reason' => $log->reason,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function reportStatusCounts(User $user, array $period): array
    {
        $counts = ['waiting_review' => 0, 'approved' => 0, 'revisions' => 0, 'locked' => 0, 'draft' => 0, 'in_review' => 0, 'submitted' => 0];
        if ($period['academicYear'] === null || $period['semester'] === null) {
            return $counts;
        }
        $cards = $this->reportCards($user, $period)
            ->with(['enrollment.academicClass.subjectAssignments', 'enrollment.student', 'semester.academicYear', 'grades'])
            ->get();

        foreach ($cards as $card) {
            if ($card->status === 'LOCKED') {
                $counts['locked']++;

                continue;
            }

            if ($this->completeness->summary($card)['complete']) {
                $counts['approved']++;

                continue;
            }

            $key = match ($card->status) {
                'SUBMITTED' => 'submitted',
                'IN_REVIEW' => 'in_review',
                'NEEDS_REVISION' => 'revisions',
                default => 'draft',
            };
            $counts[$key]++;
        }
        $counts['waiting_review'] = $counts['draft'] + $counts['submitted'] + $counts['in_review'] + $counts['revisions'];

        return $counts;
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function reportCards(User $user, array $period): Builder
    {
        return ReportCard::query()
            ->select('report_cards.*')
            ->join('enrollments', 'enrollments.id', '=', 'report_cards.enrollment_id')
            ->join('academic_classes', 'academic_classes.id', '=', 'enrollments.class_id')
            ->join('programs', 'programs.id', '=', 'academic_classes.program_id')
            ->where('programs.institution_id', $user->institution_id)
            ->where('academic_classes.academic_year_id', $period['academicYear']->getKey())
            ->where('report_cards.semester_id', $period['semester']->getKey());
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function adminClassProgress(User $user, array $period): Collection
    {
        $yearId = $period['academicYear']->getKey();
        $classes = AcademicClass::query()->where('academic_year_id', $yearId)->where('status', 'ACTIVE')->whereHas('program', fn (Builder $query) => $query->where('institution_id', $user->institution_id))->withCount([
            'enrollments as student_count' => fn (Builder $query) => $query->where('academic_year_id', $yearId)->where('enrollment_status', 'ACTIVE'),
            'subjectAssignments as assignment_count' => fn (Builder $query) => $query->where('active', true),
        ])->orderBy('name')->get();
        $filled = ReportGrade::query()->join('report_cards', 'report_cards.id', '=', 'report_grades.report_card_id')->join('enrollments', 'enrollments.id', '=', 'report_cards.enrollment_id')->where('report_cards.semester_id', $period['semester']->getKey())->where('enrollments.academic_year_id', $yearId)->whereNotNull('report_grades.score')->selectRaw('enrollments.class_id, count(report_grades.id) as filled_count')->groupBy('enrollments.class_id')->pluck('filled_count', 'class_id');

        return $classes->map(function (AcademicClass $class) use ($filled): AcademicClass {
            $expected = $class->student_count * $class->assignment_count;
            $class->setAttribute('expected_grade_count', $expected);
            $class->setAttribute('filled_grade_count', (int) ($filled[$class->getKey()] ?? 0));
            $class->setAttribute('progress_percent', $expected > 0 ? (int) round(($class->filled_grade_count / $expected) * 100) : 0);

            return $class;
        });
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function recentTransfers(User $user, array $period): Collection
    {
        return ClassTransfer::query()->with(['student', 'fromEnrollment.academicClass', 'toEnrollment.academicClass'])->whereHas('toEnrollment.academicClass.program', fn (Builder $query) => $query->where('institution_id', $user->institution_id))->whereHas('toEnrollment', fn (Builder $query) => $query->where('academic_year_id', $period['academicYear']->getKey()))->latest('effective_date')->latest('id')->limit(5)->get();
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function teacherAssignments(User $user, array $period): Collection
    {
        $yearId = $period['academicYear']->getKey();
        $assignments = SubjectAssignment::query()->with(['subject', 'academicClass'])->where('teacher_id', $user->getKey())->where('active', true)->whereHas('academicClass', fn (Builder $query) => $query->where('academic_year_id', $yearId)->whereHas('program', fn (Builder $program) => $program->where('institution_id', $user->institution_id)))->orderBy('display_order')->get();
        $students = Enrollment::query()->where('academic_year_id', $yearId)->where('enrollment_status', 'ACTIVE')->whereIn('class_id', $assignments->pluck('class_id'))->selectRaw('class_id, count(*) as student_count')->groupBy('class_id')->pluck('student_count', 'class_id');
        $filled = ReportGrade::query()->join('report_cards', 'report_cards.id', '=', 'report_grades.report_card_id')->join('enrollments', 'enrollments.id', '=', 'report_cards.enrollment_id')->join('subject_assignments', 'subject_assignments.id', '=', 'report_grades.subject_assignment_id')->where('report_cards.semester_id', $period['semester']->getKey())->where('enrollments.academic_year_id', $yearId)->where('subject_assignments.teacher_id', $user->getKey())->whereNotNull('report_grades.score')->selectRaw('report_grades.subject_assignment_id, count(report_grades.id) as filled_count')->groupBy('report_grades.subject_assignment_id')->pluck('filled_count', 'subject_assignment_id');

        return $assignments->map(function (SubjectAssignment $assignment) use ($students, $filled): SubjectAssignment {
            $expected = (int) ($students[$assignment->class_id] ?? 0);
            $assignment->setAttribute('student_count', $expected);
            $assignment->setAttribute('filled_count', (int) ($filled[$assignment->getKey()] ?? 0));
            $assignment->setAttribute('progress_percent', $expected > 0 ? (int) round(($assignment->filled_count / $expected) * 100) : 0);

            return $assignment;
        });
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function teacherReportStatus(User $user, array $period): array
    {
        $grouped = $this->reportCards($user, $period)->whereHas('enrollment.academicClass.subjectAssignments', fn (Builder $assignment) => $assignment->where('teacher_id', $user->getKey()))->select('report_cards.status')->selectRaw('count(distinct report_cards.id) as aggregate')->groupBy('report_cards.status')->pluck('aggregate', 'status');

        return ['drafts' => (int) ($grouped['DRAFT'] ?? 0), 'revisions' => (int) ($grouped['NEEDS_REVISION'] ?? 0)];
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function homeroomClasses(User $user, array $period): Collection
    {
        return AcademicClass::query()->where('academic_year_id', $period['academicYear']->getKey())->where('homeroom_teacher_id', $user->getKey())->where('status', 'ACTIVE')->whereHas('program', fn (Builder $query) => $query->where('institution_id', $user->institution_id))->withCount(['enrollments as student_count' => fn (Builder $query) => $query->where('academic_year_id', $period['academicYear']->getKey())->where('enrollment_status', 'ACTIVE')])->orderBy('name')->get();
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function kepsekClassSummary(User $user, array $period): Collection
    {
        $classes = AcademicClass::query()->where('academic_year_id', $period['academicYear']->getKey())->where('status', 'ACTIVE')->whereHas('program', fn (Builder $query) => $query->where('institution_id', $user->institution_id))->withCount(['enrollments as student_count' => fn (Builder $query) => $query->where('academic_year_id', $period['academicYear']->getKey())->where('enrollment_status', 'ACTIVE')])->orderBy('name')->get();
        $grouped = $this->reportCards($user, $period)->select('academic_classes.id as class_id', 'report_cards.status')->selectRaw('count(*) as aggregate')->groupBy('academic_classes.id', 'report_cards.status')->get()->groupBy('class_id');
        $cardsByClass = $this->reportCards($user, $period)
            ->with(['enrollment.academicClass.subjectAssignments', 'grades'])
            ->get()
            ->groupBy(fn (ReportCard $card): ?int => $card->enrollment?->class_id);

        return $classes->map(function (AcademicClass $class) use ($grouped, $cardsByClass): AcademicClass {
            $statuses = $grouped->get($class->getKey(), collect())->pluck('aggregate', 'status');
            $cards = $cardsByClass->get($class->getKey(), collect());
            $readyCount = $cards->filter(fn (ReportCard $card): bool => $this->completeness->summary($card)['complete'])->count();
            $class->setAttribute('report_count', $cards->count());
            $class->setAttribute('waiting_review_count', (int) ($statuses['SUBMITTED'] ?? 0) + (int) ($statuses['IN_REVIEW'] ?? 0));
            $class->setAttribute('approved_count', $readyCount);
            $class->setAttribute('ready_count', $readyCount);
            $class->setAttribute('incomplete_count', max(0, $cards->count() - $readyCount));
            $class->setAttribute('workflow_approved_count', (int) ($statuses['APPROVED'] ?? 0));
            $class->setAttribute('revision_count', (int) ($statuses['NEEDS_REVISION'] ?? 0));
            $class->setAttribute('locked_count', (int) ($statuses['LOCKED'] ?? 0));
            $class->setAttribute('progress_percent', $cards->count() > 0 ? (int) round(($readyCount / $cards->count()) * 100) : 0);

            return $class;
        });
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function reviewQueue(User $user, array $period): Collection
    {
        return ReportCard::query()->with(['enrollment.student', 'enrollment.academicClass'])->whereHas('enrollment.academicClass.program', fn (Builder $query) => $query->where('institution_id', $user->institution_id))->whereHas('enrollment.academicClass', fn (Builder $query) => $query->where('academic_year_id', $period['academicYear']->getKey()))->where('semester_id', $period['semester']->getKey())->where('status', '!=', 'LOCKED')->latest('updated_at')->limit(12)->get();
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function emptyDashboard(array $period): array
    {
        return ['role' => null, 'roleLabel' => 'Pengguna', 'period' => $period, 'hasPeriod' => false, 'stats' => [], 'message' => 'Akun ini belum memiliki role operasional untuk dashboard.'];
    }

    private function resolveRole(User $user, ?string $requestedRole): ?string
    {
        // A requested role is accepted only when the account actually owns it;
        // the dashboard selector must never become a privilege-escalation path.
        if ($requestedRole !== null && $user->hasRole($requestedRole)) {
            return $requestedRole;
        }
        foreach ([RoleCode::Admin->value, RoleCode::Guru->value, RoleCode::Kepsek->value] as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }
}
