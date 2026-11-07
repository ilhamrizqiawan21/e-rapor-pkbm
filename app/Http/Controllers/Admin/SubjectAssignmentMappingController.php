<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkAssignSubjectTeacherRequest;
use App\Http\Requests\Admin\BulkStoreSubjectAssignmentsRequest;
use App\Http\Requests\Admin\CopySubjectAssignmentRequest;
use App\Http\Requests\Admin\ReorderSubjectAssignmentsRequest;
use App\Models\AcademicClass;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\SubjectAssignmentMappingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubjectAssignmentMappingController extends Controller
{
    public function __construct(private readonly SubjectAssignmentMappingService $mappingService) {}

    public function bulkCreate(Request $request): View
    {
        Gate::authorize('create', SubjectAssignment::class);

        return view('admin.subject-assignments.bulk', [
            'classes' => AcademicClass::query()
                ->with(['academicYear', 'program', 'level'])
                ->whereHas('program', fn ($query) => $query->where('institution_id', $request->user()->institution_id))
                ->orderByDesc('academic_year_id')
                ->orderBy('name')
                ->get(),
            'subjects' => Subject::query()
                ->where('institution_id', $request->user()->institution_id)
                ->where('active', true)
                ->orderBy('name')
                ->get(),
            'teachers' => $this->teachers($request->user()),
        ]);
    }

    public function bulkStore(BulkStoreSubjectAssignmentsRequest $request): RedirectResponse
    {
        $result = $this->mappingService->bulkAdd(
            classIds: $request->validated('class_ids'),
            subjectIds: $request->validated('subject_ids'),
            teacherId: (int) $request->validated('teacher_id'),
            minimumScore: (int) $request->validated('minimum_score'),
            actor: $request->user(),
        );

        return redirect()
            ->back()
            ->with('status', sprintf(
                'Penugasan selesai: %d penugasan ditambahkan, %d penugasan yang sudah ada dilewati.',
                $result['created'],
                $result['skipped'],
            ));
    }

    public function copyCreate(Request $request, AcademicClass $academicClass): View
    {
        Gate::authorize('create', SubjectAssignment::class);
        Gate::authorize('view', $academicClass);

        return view('admin.subject-assignments.copy', [
            'targetClass' => $academicClass->load(['academicYear', 'program', 'level']),
            'sourceClasses' => AcademicClass::query()
                ->with(['academicYear', 'program', 'level'])
                ->where('id', '!=', $academicClass->getKey())
                ->where('academic_year_id', $academicClass->academic_year_id)
                ->whereHas('program', fn ($query) => $query->where('institution_id', $request->user()->institution_id))
                ->whereHas('subjectAssignments')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function copyStore(CopySubjectAssignmentRequest $request, AcademicClass $academicClass): RedirectResponse
    {
        $source = AcademicClass::query()->findOrFail((int) $request->validated('source_class_id'));
        $result = $this->mappingService->copyConfiguration($source, $academicClass, $request->user());

        return redirect()
            ->route('admin.academic-classes.subject-assignments.index', $academicClass)
            ->with('status', sprintf(
                'Konfigurasi disalin: %d ditambahkan, %d diperbarui, %d tidak berubah.',
                $result['created'],
                $result['updated'],
                $result['skipped'],
            ));
    }

    public function reorder(ReorderSubjectAssignmentsRequest $request, AcademicClass $academicClass): RedirectResponse
    {
        $this->mappingService->reorder($academicClass, $request->validated('assignment_ids'), $request->user());

        return redirect()
            ->route('admin.academic-classes.subject-assignments.index', $academicClass)
            ->with('status', 'Urutan mata pelajaran berhasil diperbarui.');
    }

    public function reorderCreate(AcademicClass $academicClass): View
    {
        Gate::authorize('create', SubjectAssignment::class);
        Gate::authorize('view', $academicClass);

        return view('admin.subject-assignments.reorder', [
            'academicClass' => $academicClass->load(['academicYear', 'program', 'level']),
            'assignments' => $academicClass->subjectAssignments()
                ->with(['subject', 'teacher'])
                ->orderBy('display_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function bulkAssignCreate(Request $request): View
    {
        Gate::authorize('create', SubjectAssignment::class);

        return view('admin.subject-assignments.bulk-teacher', [
            'assignments' => SubjectAssignment::query()
                ->with(['academicClass.academicYear', 'subject', 'teacher'])
                ->whereHas('academicClass.program', fn ($query) => $query->where('institution_id', $request->user()->institution_id))
                ->whereHas('academicClass.academicYear', fn ($query) => $query->where('status', '!=', 'CLOSED'))
                ->orderBy('class_id')
                ->orderBy('display_order')
                ->get(),
            'teachers' => $this->teachers($request->user()),
        ]);
    }

    public function bulkAssignTeacher(BulkAssignSubjectTeacherRequest $request): RedirectResponse
    {
        $count = $this->mappingService->bulkAssignTeacher(
            assignmentIds: $request->validated('assignment_ids'),
            teacherId: (int) $request->validated('teacher_id'),
            actor: $request->user(),
        );

        return redirect()->back()->with('status', sprintf('%d penugasan berhasil diberikan kepada guru baru.', $count));
    }

    /**
     * @return Collection<int, User>
     */
    private function teachers(User $user)
    {
        return User::query()
            ->where('institution_id', $user->institution_id)
            ->where('status', 'ACTIVE')
            ->whereHas('roles', fn ($roles) => $roles->where('code', 'GURU'))
            ->with('staffProfile')
            ->orderBy('name')
            ->get();
    }
}
