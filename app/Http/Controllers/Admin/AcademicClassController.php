<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicClassRequest;
use App\Http\Requests\Admin\UpdateAcademicClassRequest;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\PeriodPlacement;
use App\Models\Program;
use App\Models\User;
use App\Services\HomeroomAssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AcademicClassController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AcademicClass::class);

        $user = $request->user();
        $classes = $this->visibleClasses($user)
            ->with(['academicYear', 'program', 'level', 'homeroomTeacher'])
            ->withCount([
                'enrollments as active_enrollments_count' => fn ($query) => $query->where('enrollment_status', 'ACTIVE'),
                'subjectAssignments as active_subject_assignments_count' => fn ($query) => $query->where('active', true),
            ])
            ->orderByDesc('academic_year_id')
            ->orderBy('program_id')
            ->orderBy('level_id')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin/academic-classes/index', [
            'classes' => $classes,
            'modalAcademicClass' => new AcademicClass(['status' => 'ACTIVE']),
            ...$this->formOptions($user),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', AcademicClass::class);

        return view('admin/academic-classes/create', [
            'academicClass' => new AcademicClass(['status' => 'ACTIVE']),
            ...$this->formOptions($request->user()),
        ]);
    }

    public function store(StoreAcademicClassRequest $request, HomeroomAssignmentService $homeroomService): RedirectResponse
    {
        Gate::authorize('create', AcademicClass::class);

        $academicYear = $this->institutionAcademicYear($request->user(), $request->validated('academic_year_id'));
        $this->ensurePeriodOpen($academicYear);

        $data = $request->validated();
        $teacherId = $data['homeroom_teacher_id'] ?? null;
        unset($data['homeroom_teacher_id']);

        $academicClass = AcademicClass::query()->create($data);

        if ($teacherId !== null && $teacherId !== '') {
            $homeroomService->assign(
                $academicClass,
                User::query()->findOrFail($teacherId),
                $request->user(),
            );
        }

        return redirect()
            ->route('admin.academic-classes.show', $academicClass)
            ->with('status', 'Kelas berhasil dibuat.');
    }

    public function show(Request $request, AcademicClass $academicClass): View
    {
        Gate::authorize('view', $academicClass);

        $academicClass->load([
            'academicYear.semesters',
            'program',
            'level',
            'homeroomTeacher.staffProfile',
            'homeroomAssignments.teacher.staffProfile',
            'enrollments' => fn ($query) => $query->where('enrollment_status', 'ACTIVE')->with('student')->orderBy('id'),
            'enrollments.reportCards.grades',
            'subjectAssignments' => fn ($query) => $query->where('active', true)->with(['subject', 'teacher']),
        ]);

        $progress = $this->reportProgress($academicClass);

        return view('admin/academic-classes/show', compact('academicClass', 'progress'));
    }

    public function edit(Request $request, AcademicClass $academicClass): View
    {
        Gate::authorize('update', $academicClass);

        return view('admin/academic-classes/edit', [
            'academicClass' => $academicClass->load(['academicYear', 'program', 'level']),
            ...$this->formOptions($request->user()),
        ]);
    }

    public function update(UpdateAcademicClassRequest $request, AcademicClass $academicClass, HomeroomAssignmentService $homeroomService): RedirectResponse
    {
        Gate::authorize('update', $academicClass);

        $this->ensurePeriodOpen($academicClass->academicYear);
        $academicYear = $this->institutionAcademicYear($request->user(), $request->validated('academic_year_id'));
        $this->ensurePeriodOpen($academicYear);

        $data = $request->validated();
        $teacherId = $data['homeroom_teacher_id'] ?? null;
        $previousTeacherId = $academicClass->homeroom_teacher_id;
        unset($data['homeroom_teacher_id']);

        $academicClass->update($data);

        if ((string) $teacherId !== (string) $previousTeacherId) {
            if ($teacherId === null || $teacherId === '') {
                $homeroomService->remove($academicClass, $request->user());
            } else {
                $homeroomService->reassign(
                    $academicClass,
                    User::query()->findOrFail($teacherId),
                    $request->user(),
                );
            }
        }

        return redirect()
            ->route('admin.academic-classes.show', $academicClass)
            ->with('status', 'Kelas berhasil diperbarui.');
    }

    public function destroy(AcademicClass $academicClass): RedirectResponse
    {
        Gate::authorize('delete', $academicClass);
        $this->ensurePeriodOpen($academicClass->academicYear);

        abort_if(
            $academicClass->enrollments()->exists()
                || $academicClass->subjectAssignments()->exists()
                || PeriodPlacement::query()->where('class_id', $academicClass->getKey())->exists(),
            422,
            'Kelas yang sudah memiliki siswa, penugasan mapel, atau penempatan tahun baru tidak dapat dihapus. Nonaktifkan kelas untuk mempertahankan histori.',
        );

        $academicClass->delete();

        return redirect()
            ->route('admin.academic-classes.index')
            ->with('status', 'Kelas berhasil dihapus.');
    }

    /**
     * @return Builder<AcademicClass>
     */
    private function visibleClasses(User $user): Builder
    {
        $query = AcademicClass::query()->whereHas('program', fn ($programs) => $programs->where('institution_id', $user->institution_id));

        if ($user->hasAnyRole(['ADMIN', 'KEPSEK'])) {
            return $query;
        }

        return $query->where(function ($classes) use ($user): void {
            $classes->where('homeroom_teacher_id', $user->getKey())
                ->orWhereHas('subjectAssignments', fn ($assignments) => $assignments->where('teacher_id', $user->getKey()));
        });
    }

    /**
     * @return array{academicYears: Collection<int, AcademicYear>, programs: Collection<int, Program>, levels: Collection<int, Level>, teachers: Collection<int, User>}
     */
    private function formOptions(User $user): array
    {
        return [
            'academicYears' => AcademicYear::query()
                ->where('institution_id', $user->institution_id)
                ->orderByDesc('name')
                ->get(),
            'programs' => Program::query()
                ->where('institution_id', $user->institution_id)
                ->with('levels')
                ->orderBy('name')
                ->get(),
            'levels' => Level::query()
                ->whereHas('program', fn ($programs) => $programs->where('institution_id', $user->institution_id))
                ->with('program')
                ->orderBy('program_id')
                ->orderBy('number')
                ->get(),
            'teachers' => User::query()
                ->where('institution_id', $user->institution_id)
                ->where('status', 'ACTIVE')
                ->whereHas('roles', fn ($roles) => $roles->where('code', 'GURU'))
                ->with('staffProfile')
                ->orderBy('name')
                ->get(),
        ];
    }

    private function institutionAcademicYear(User $user, mixed $id): AcademicYear
    {
        return AcademicYear::query()
            ->where('institution_id', $user->institution_id)
            ->findOrFail($id);
    }

    private function ensurePeriodOpen(AcademicYear $academicYear): void
    {
        $status = $academicYear->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;

        abort_if(
            strtoupper($statusValue) === 'CLOSED',
            422,
            'Periode akademik sudah ditutup dan tidak menerima perubahan kelas.',
        );
    }

    /**
     * @return array{students: int, semesters: int, expected: int, completed: int, percentage: int, has_assignments: bool}
     */
    private function reportProgress(AcademicClass $academicClass): array
    {
        $enrollments = $academicClass->enrollments;
        $assignmentCount = $academicClass->subjectAssignments->count();
        $semesterCount = $academicClass->academicYear->semesters->count();
        $expected = $enrollments->count() * $semesterCount;
        $completed = $enrollments
            ->flatMap(fn ($enrollment) => $enrollment->reportCards)
            ->filter(fn ($reportCard) => $assignmentCount > 0 && $reportCard->grades->whereNotNull('score')->count() >= $assignmentCount)
            ->count();

        return [
            'students' => $enrollments->count(),
            'semesters' => $semesterCount,
            'expected' => $expected,
            'completed' => min($completed, $expected),
            'percentage' => $expected > 0 ? min(100, (int) round(($completed / $expected) * 100)) : 0,
            'has_assignments' => $assignmentCount > 0,
        ];
    }
}
