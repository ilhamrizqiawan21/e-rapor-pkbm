<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\Enrollment;
use App\Models\ReportGrade;
use App\Models\Semester;
use App\Models\SubjectAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class GradeMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        abort_unless(
            $actor->isActive()
                && $actor->institution_id !== null
                && $actor->hasAnyRole(['ADMIN', 'KEPSEK']),
            403,
        );

        $semesters = Semester::query()
            ->with('academicYear')
            ->whereHas('academicYear', fn (Builder $query): Builder => $query->where('institution_id', $actor->institution_id))
            ->orderByDesc('academic_year_id')
            ->orderBy('sequence')
            ->get();
        $selectedSemester = $this->selectedSemester($semesters, $request->integer('semester_id'), $request->integer('academic_year_id'));
        $selectedYearId = $selectedSemester?->academic_year_id ?? $request->integer('academic_year_id');

        $academicYears = $semesters->pluck('academicYear')->filter()->unique('id')->values();
        $classes = AcademicClass::query()
            ->with(['program', 'level'])
            ->whereHas('program', fn (Builder $query): Builder => $query->where('institution_id', $actor->institution_id))
            ->when($selectedYearId, fn (Builder $query, int $yearId): Builder => $query->where('academic_year_id', $yearId))
            ->when($request->integer('class_id'), fn (Builder $query, int $classId): Builder => $query->whereKey($classId))
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        $assignments = SubjectAssignment::query()
            ->with(['subject', 'teacher.staffProfile', 'academicClass.program', 'academicClass.level'])
            ->where('active', true)
            ->whereHas('academicClass', function (Builder $query) use ($actor, $selectedYearId, $request): void {
                $query
                    ->where('status', 'ACTIVE')
                    ->whereHas('program', fn (Builder $program): Builder => $program->where('institution_id', $actor->institution_id))
                    ->when($selectedYearId, fn (Builder $classQuery, int $yearId): Builder => $classQuery->where('academic_year_id', $yearId))
                    ->when($request->integer('class_id'), fn (Builder $classQuery, int $classId): Builder => $classQuery->whereKey($classId));
            })
            ->when($request->integer('teacher_id'), fn (Builder $query, int $teacherId): Builder => $query->where('teacher_id', $teacherId))
            ->when($request->integer('subject_id'), fn (Builder $query, int $subjectId): Builder => $query->where('subject_id', $subjectId))
            ->orderBy('class_id')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $classIds = $assignments->pluck('class_id')->unique()->values();
        $expectedByClass = $classIds->isEmpty()
            ? collect()
            : Enrollment::query()
                ->whereIn('class_id', $classIds)
                ->where('enrollment_status', 'ACTIVE')
                ->when($selectedYearId, fn (Builder $query, int $yearId): Builder => $query->where('academic_year_id', $yearId))
                ->select('class_id')
                ->selectRaw('count(*) as aggregate')
                ->groupBy('class_id')
                ->pluck('aggregate', 'class_id');

        $filledByAssignment = $selectedSemester === null || $assignments->isEmpty()
            ? collect()
            : ReportGrade::query()
                ->join('report_cards', 'report_cards.id', '=', 'report_grades.report_card_id')
                ->join('enrollments', 'enrollments.id', '=', 'report_cards.enrollment_id')
                ->where('report_cards.semester_id', $selectedSemester->getKey())
                ->whereIn('report_grades.subject_assignment_id', $assignments->pluck('id'))
                ->whereNotNull('report_grades.score')
                ->whereNotNull('report_grades.description')
                ->where('report_grades.description', '<>', '')
                ->select('report_grades.subject_assignment_id')
                ->selectRaw('count(distinct enrollments.id) as aggregate')
                ->groupBy('report_grades.subject_assignment_id')
                ->pluck('aggregate', 'subject_assignment_id');

        $rows = $assignments->map(function (SubjectAssignment $assignment) use ($expectedByClass, $filledByAssignment): SubjectAssignment {
            $expected = (int) ($expectedByClass[$assignment->class_id] ?? 0);
            $filled = (int) ($filledByAssignment[$assignment->getKey()] ?? 0);
            $assignment->setAttribute('expected_count', $expected);
            $assignment->setAttribute('filled_count', $filled);
            $assignment->setAttribute('missing_count', max(0, $expected - $filled));
            $assignment->setAttribute('progress_percent', $expected > 0 ? (int) round(($filled / $expected) * 100) : 0);

            return $assignment;
        });

        $summary = [
            'assignments' => $rows->count(),
            'complete_assignments' => $rows->where('missing_count', 0)->where('expected_count', '>', 0)->count(),
            'expected_grades' => $rows->sum('expected_count'),
            'filled_grades' => $rows->sum('filled_count'),
            'missing_grades' => $rows->sum('missing_count'),
        ];
        $summary['progress_percent'] = $summary['expected_grades'] > 0
            ? (int) round(($summary['filled_grades'] / $summary['expected_grades']) * 100)
            : 0;

        $teacherSummary = $rows
            ->groupBy('teacher_id')
            ->map(function (Collection $teacherRows): array {
                $first = $teacherRows->first();
                $expected = $teacherRows->sum('expected_count');
                $filled = $teacherRows->sum('filled_count');

                return [
                    'name' => $first?->teacher?->name ?? 'Guru belum ditentukan',
                    'assignments' => $teacherRows->count(),
                    'expected' => $expected,
                    'filled' => $filled,
                    'missing' => $teacherRows->sum('missing_count'),
                    'progress_percent' => $expected > 0 ? (int) round(($filled / $expected) * 100) : 0,
                ];
            })
            ->sortBy(fn (array $teacher): array => [$teacher['progress_percent'], $teacher['name']])
            ->values();

        $subjectOptions = $assignments->pluck('subject')->filter()->unique('id')->sortBy('name')->values();
        $teacherOptions = $assignments->pluck('teacher')->filter()->unique('id')->sortBy('name')->values();
        $filters = $request->only(['academic_year_id', 'semester_id', 'class_id', 'subject_id', 'teacher_id']);

        return view('admin.grade-monitoring.index', compact(
            'academicYears',
            'classes',
            'filters',
            'rows',
            'selectedSemester',
            'semesters',
            'subjectOptions',
            'summary',
            'teacherOptions',
            'teacherSummary',
        ));
    }

    private function selectedSemester(Collection $semesters, ?int $semesterId, ?int $academicYearId): ?Semester
    {
        if ($semesterId) {
            return $semesters->firstWhere('id', $semesterId);
        }

        $yearSemesters = $academicYearId
            ? $semesters->where('academic_year_id', $academicYearId)
            : $semesters;

        return $yearSemesters->first(fn (Semester $semester): bool => $semester->status?->value === 'ACTIVE') ?? $yearSemesters->first();
    }
}
