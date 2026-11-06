<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcademicYearStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubjectAssignmentRequest;
use App\Http\Requests\Admin\UpdateSubjectAssignmentRequest;
use App\Models\AcademicClass;
use App\Models\ReportCard;
use App\Models\ReportGrade;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubjectAssignmentController extends Controller
{
    public function index(Request $request, AcademicClass $academicClass): View
    {
        Gate::authorize('view', $academicClass);

        $query = $academicClass->subjectAssignments()
            ->with(['subject', 'teacher.staffProfile'])
            ->orderBy('display_order')
            ->orderBy('id');

        if ($request->user()->hasRole('GURU')) {
            $query->where('teacher_id', $request->user()->getKey());
        }

        return view('admin.subject-assignments.index', [
            'academicClass' => $academicClass->load(['academicYear', 'program', 'level']),
            'assignments' => $query->get(),
            'modalAssignment' => new SubjectAssignment(['minimum_score' => 70, 'display_order' => 1, 'active' => true]),
            ...$this->formOptions($request->user()),
        ]);
    }

    public function create(Request $request, AcademicClass $academicClass): View
    {
        Gate::authorize('create', SubjectAssignment::class);
        Gate::authorize('view', $academicClass);
        $this->ensurePeriodOpen($academicClass);

        return view('admin.subject-assignments.create', [
            'academicClass' => $academicClass->load(['academicYear', 'program', 'level']),
            'assignment' => new SubjectAssignment(['minimum_score' => 70, 'display_order' => 1, 'active' => true]),
            ...$this->formOptions($request->user()),
        ]);
    }

    public function store(StoreSubjectAssignmentRequest $request, AcademicClass $academicClass): RedirectResponse
    {
        Gate::authorize('create', SubjectAssignment::class);
        Gate::authorize('view', $academicClass);
        $this->ensurePeriodOpen($academicClass);

        $academicClass->subjectAssignments()->create($request->validated());

        return redirect()
            ->route('admin.academic-classes.subject-assignments.index', $academicClass)
            ->with('status', 'Penugasan mata pelajaran berhasil dibuat.');
    }

    public function edit(Request $request, AcademicClass $academicClass, SubjectAssignment $subjectAssignment): View
    {
        Gate::authorize('update', $subjectAssignment);
        $this->assertAssignmentBelongsToClass($academicClass, $subjectAssignment);
        $this->ensurePeriodOpen($academicClass);

        return view('admin.subject-assignments.edit', [
            'academicClass' => $academicClass->load(['academicYear', 'program', 'level']),
            'assignment' => $subjectAssignment->load(['subject', 'teacher.staffProfile']),
            ...$this->formOptions($request->user(), $subjectAssignment),
        ]);
    }

    public function update(
        UpdateSubjectAssignmentRequest $request,
        AcademicClass $academicClass,
        SubjectAssignment $subjectAssignment,
    ): RedirectResponse {
        Gate::authorize('update', $subjectAssignment);
        $this->assertAssignmentBelongsToClass($academicClass, $subjectAssignment);
        $this->ensurePeriodOpen($academicClass);
        $this->ensureUnlockedGrades($subjectAssignment);

        $subjectAssignment->update($request->validated());

        return redirect()
            ->route('admin.academic-classes.subject-assignments.index', $academicClass)
            ->with('status', 'Penugasan mata pelajaran berhasil diperbarui.');
    }

    public function destroy(AcademicClass $academicClass, SubjectAssignment $subjectAssignment): RedirectResponse
    {
        Gate::authorize('delete', $subjectAssignment);
        $this->assertAssignmentBelongsToClass($academicClass, $subjectAssignment);
        $this->ensurePeriodOpen($academicClass);
        $this->ensureUnlockedGrades($subjectAssignment);

        $subjectAssignment->delete();

        return redirect()
            ->route('admin.academic-classes.subject-assignments.index', $academicClass)
            ->with('status', 'Penugasan mata pelajaran berhasil dihapus.');
    }

    /**
     * @return array{subjects: Collection<int, Subject>, teachers: Collection<int, User>}
     */
    private function formOptions(User $user, ?SubjectAssignment $assignment = null): array
    {
        $subjects = Subject::query()
            ->where('institution_id', $user->institution_id)
            ->where(function ($query) use ($assignment): void {
                $query->where('active', true);

                if ($assignment?->subject_id !== null) {
                    $query->orWhereKey($assignment->subject_id);
                }
            })
            ->orderBy('name')
            ->get();

        return [
            'subjects' => $subjects,
            'teachers' => User::query()
                ->where('institution_id', $user->institution_id)
                ->where('status', 'ACTIVE')
                ->whereHas('roles', fn ($roles) => $roles->where('code', 'GURU'))
                ->with('staffProfile')
                ->orderBy('name')
                ->get(),
        ];
    }

    private function assertAssignmentBelongsToClass(AcademicClass $academicClass, SubjectAssignment $subjectAssignment): void
    {
        abort_unless($subjectAssignment->class_id === $academicClass->getKey(), 404);
    }

    private function ensurePeriodOpen(AcademicClass $academicClass): void
    {
        $academicClass->loadMissing('academicYear');

        abort_if(
            $academicClass->academicYear->status === AcademicYearStatus::Closed,
            422,
            'Periode akademik sudah ditutup dan tidak menerima perubahan penugasan mata pelajaran.',
        );
    }

    private function ensureUnlockedGrades(SubjectAssignment $subjectAssignment): void
    {
        $hasLockedGrades = ReportGrade::query()
            ->where('subject_assignment_id', $subjectAssignment->getKey())
            ->whereIn('report_card_id', ReportCard::query()->select('id')->where('status', 'LOCKED'))
            ->exists();

        abort_if(
            $hasLockedGrades,
            422,
            'Penugasan tidak dapat diubah karena sudah memiliki nilai pada rapor terkunci.',
        );
    }
}
