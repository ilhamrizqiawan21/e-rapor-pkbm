<?php

namespace App\Services;

use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\ClassTransfer;
use App\Models\Enrollment;
use App\Models\ReportCard;
use App\Models\ReportGrade;
use App\Models\Semester;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class DashboardService
{
    /** @return array<string, mixed> */
    public function for(User $user, ?string $requestedRole = null): array
    {
        $user->loadMissing('roles');
        $role = $this->resolveRole($user, $requestedRole);
        $period = $this->activePeriod($user->institution_id);

        return match ($role) {
            RoleCode::Admin->value => $this->admin($user, $period),
            RoleCode::Guru->value => $this->guru($user, $period),
            RoleCode::Kepsek->value => $this->kepsek($user, $period),
            default => $this->emptyDashboard($period),
        };
    }

    /** @return array{academicYear: ?AcademicYear, semester: ?Semester, label: string} */
    private function activePeriod(?int $institutionId): array
    {
        if ($institutionId === null) {
            return ['academicYear' => null, 'semester' => null, 'label' => 'Belum dikonfigurasi'];
        }

        $academicYear = AcademicYear::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'ACTIVE')
            ->with(['semesters' => fn ($query) => $query->where('status', 'ACTIVE')->orderBy('sequence')])
            ->first();
        $semester = $academicYear?->semesters->first();

        return [
            'academicYear' => $academicYear,
            'semester' => $semester,
            'label' => $academicYear === null
                ? 'Belum ada tahun pelajaran aktif'
                : ($semester === null ? $academicYear->name.' · semester belum aktif' : $academicYear->name.' · '.$semester->name),
        ];
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
        $teacherCount = User::query()->where('institution_id', $institutionId)->where('status', 'ACTIVE')->whereHas('roles', fn (Builder $query) => $query->where('code', RoleCode::Guru->value))->count();
        $statusCounts = $this->reportStatusCounts($user, $period);

        return [
            'role' => RoleCode::Admin->value,
            'roleLabel' => RoleCode::Admin->label(),
            'period' => $period,
            'hasPeriod' => $hasPeriod,
            'stats' => [
                ['label' => 'Kelas aktif', 'value' => $classCount, 'detail' => 'Pada periode aktif'],
                ['label' => 'Peserta didik', 'value' => $studentCount, 'detail' => 'Enrollment aktif'],
                ['label' => 'Guru aktif', 'value' => $teacherCount, 'detail' => 'Dalam institusi'],
                ['label' => 'Menunggu review', 'value' => $statusCounts['waiting_review'], 'detail' => 'SUBMITTED + IN_REVIEW'],
            ],
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
                ['label' => 'Belum diajukan', 'value' => $reportStatus['drafts'], 'detail' => 'Rapor masih DRAFT'],
                ['label' => 'Perlu revisi', 'value' => $reportStatus['revisions'], 'detail' => 'Dikembalikan Kepsek'],
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

        return [
            'role' => RoleCode::Kepsek->value,
            'roleLabel' => RoleCode::Kepsek->label(),
            'period' => $period,
            'hasPeriod' => $hasPeriod,
            'stats' => [
                ['label' => 'Menunggu review', 'value' => $statusCounts['waiting_review'], 'detail' => 'Submitted atau sedang ditinjau'],
                ['label' => 'Disetujui', 'value' => $statusCounts['approved'], 'detail' => 'Siap dikunci Admin'],
                ['label' => 'Dikembalikan', 'value' => $statusCounts['revisions'], 'detail' => 'Menunggu revisi Guru'],
                ['label' => 'Terkunci', 'value' => $statusCounts['locked'], 'detail' => 'Arsip final'],
            ],
            'statusCounts' => $statusCounts,
            'classSummary' => $hasPeriod ? $this->kepsekClassSummary($user, $period) : collect(),
            'reviewQueue' => $hasPeriod ? $this->reviewQueue($user, $period) : collect(),
        ];
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function reportStatusCounts(User $user, array $period): array
    {
        $counts = ['waiting_review' => 0, 'approved' => 0, 'revisions' => 0, 'locked' => 0, 'draft' => 0, 'in_review' => 0, 'submitted' => 0];
        if ($period['academicYear'] === null || $period['semester'] === null) {
            return $counts;
        }
        $grouped = $this->reportCards($user, $period)->select('report_cards.status')->selectRaw('count(*) as aggregate')->groupBy('report_cards.status')->pluck('aggregate', 'status');
        foreach (['draft', 'submitted', 'in_review', 'revisions', 'approved', 'locked'] as $key) {
            $status = match ($key) {
                'draft' => 'DRAFT', 'submitted' => 'SUBMITTED', 'in_review' => 'IN_REVIEW', 'revisions' => 'NEEDS_REVISION', 'approved' => 'APPROVED', 'locked' => 'LOCKED',
            };
            $counts[$key] = (int) ($grouped[$status] ?? 0);
        }
        $counts['waiting_review'] = $counts['submitted'] + $counts['in_review'];

        return $counts;
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function reportCards(User $user, array $period): Builder
    {
        return ReportCard::query()->join('enrollments', 'enrollments.id', '=', 'report_cards.enrollment_id')->join('academic_classes', 'academic_classes.id', '=', 'enrollments.class_id')->join('programs', 'programs.id', '=', 'academic_classes.program_id')->where('programs.institution_id', $user->institution_id)->where('academic_classes.academic_year_id', $period['academicYear']->getKey())->where('report_cards.semester_id', $period['semester']->getKey());
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
        $classes = AcademicClass::query()->where('academic_year_id', $period['academicYear']->getKey())->where('status', 'ACTIVE')->whereHas('program', fn (Builder $query) => $query->where('institution_id', $user->institution_id))->orderBy('name')->get();
        $grouped = $this->reportCards($user, $period)->select('academic_classes.id as class_id', 'report_cards.status')->selectRaw('count(*) as aggregate')->groupBy('academic_classes.id', 'report_cards.status')->get()->groupBy('class_id');

        return $classes->map(function (AcademicClass $class) use ($grouped): AcademicClass {
            $statuses = $grouped->get($class->getKey(), collect())->pluck('aggregate', 'status');
            $class->setAttribute('report_count', $statuses->sum());
            $class->setAttribute('waiting_review_count', (int) ($statuses['SUBMITTED'] ?? 0) + (int) ($statuses['IN_REVIEW'] ?? 0));
            $class->setAttribute('approved_count', (int) ($statuses['APPROVED'] ?? 0));
            $class->setAttribute('revision_count', (int) ($statuses['NEEDS_REVISION'] ?? 0));
            $class->setAttribute('locked_count', (int) ($statuses['LOCKED'] ?? 0));

            return $class;
        });
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function reviewQueue(User $user, array $period): Collection
    {
        return ReportCard::query()->with(['enrollment.student', 'enrollment.academicClass'])->whereHas('enrollment.academicClass.program', fn (Builder $query) => $query->where('institution_id', $user->institution_id))->whereHas('enrollment.academicClass', fn (Builder $query) => $query->where('academic_year_id', $period['academicYear']->getKey()))->where('semester_id', $period['semester']->getKey())->whereIn('status', ['SUBMITTED', 'IN_REVIEW'])->latest('updated_at')->limit(12)->get();
    }

    /** @param array{academicYear: ?AcademicYear, semester: ?Semester, label: string} $period */
    private function emptyDashboard(array $period): array
    {
        return ['role' => null, 'roleLabel' => 'Pengguna', 'period' => $period, 'hasPeriod' => false, 'stats' => [], 'message' => 'Akun ini belum memiliki role operasional untuk dashboard.'];
    }

    private function resolveRole(User $user, ?string $requestedRole): ?string
    {
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
