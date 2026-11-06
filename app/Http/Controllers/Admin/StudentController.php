<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Student::class);

        $activeAcademicYear = AcademicYear::query()
            ->where('institution_id', $request->user()->institution_id)
            ->where('status', 'ACTIVE')
            ->latest('start_date')
            ->latest('id')
            ->first();
        $activeAcademicYearId = $activeAcademicYear?->getKey();
        $visibleStudents = Student::query()->visibleTo($request->user());

        $students = Student::query()
            ->visibleTo($request->user())
            ->withCount([
                'enrollments',
                'enrollments as active_enrollments_count' => function (Builder $query) use ($activeAcademicYearId): void {
                    $query->where('enrollment_status', 'ACTIVE');

                    if ($activeAcademicYearId !== null) {
                        $query->where('academic_year_id', $activeAcademicYearId);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                },
            ])
            ->when($activeAcademicYearId !== null, function (Builder $query) use ($activeAcademicYearId): void {
                $query->with([
                    'enrollments' => fn ($enrollmentQuery) => $enrollmentQuery
                        ->where('academic_year_id', $activeAcademicYearId)
                        ->where('enrollment_status', 'ACTIVE')
                        ->with(['academicClass.program', 'academicClass.level'])
                        ->latest('start_date'),
                ]);
            })
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $status = strtoupper($request->string('status')->toString());

                if (in_array($status, ['ACTIVE', 'INACTIVE', 'GRADUATED', 'TRANSFERRED'], true)) {
                    $query->where('status', $status);
                }
            })
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim($request->string('search')->toString());

                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%")
                        ->orWhere('national_student_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => (clone $visibleStudents)->count(),
            'active' => (clone $visibleStudents)->where('status', 'ACTIVE')->count(),
            'unplaced' => $activeAcademicYearId === null ? null : (clone $visibleStudents)
                ->where('status', 'ACTIVE')
                ->whereDoesntHave('enrollments', fn (Builder $query) => $query
                    ->where('academic_year_id', $activeAcademicYearId)
                    ->where('enrollment_status', 'ACTIVE'))
                ->count(),
            'otherStatuses' => (clone $visibleStudents)->whereIn('status', ['INACTIVE', 'GRADUATED', 'TRANSFERRED'])->count(),
        ];

        return view('admin.students.index', compact('students', 'summary', 'activeAcademicYear'));
    }

    public function create(): View
    {
        Gate::authorize('create', Student::class);

        return view('admin.students.create', [
            'student' => new Student(['status' => 'ACTIVE']),
        ]);
    }

    public function store(StoreStudentRequest $request, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $student = Student::query()->create([
            ...$request->validated(),
            'institution_id' => $request->user()->institution_id,
        ]);
        $audit->record($request->user(), 'student.created', Student::class, $student->getKey(), null, Arr::only($student->getAttributes(), $this->auditedFields()));

        return redirect()
            ->route('admin.students.show', $student)
            ->with('status', 'Peserta didik berhasil ditambahkan.');
    }

    public function show(Student $student, Request $request, AuditLogger $audit): View
    {
        Gate::authorize('view', $student);
        $audit->record($request->user(), 'student.viewed', Student::class, $student->getKey());

        $student->load([
            'institution',
            'enrollments' => fn ($query) => $query
                ->with(['academicClass.program', 'academicClass.level', 'academicClass.academicYear', 'reportCards.semester'])
                ->latest('start_date'),
        ]);

        return view('admin.students.show', compact('student'));
    }

    public function edit(Student $student): View
    {
        Gate::authorize('update', $student);

        return view('admin.students.edit', compact('student'));
    }

    public function update(UpdateStudentRequest $request, Student $student, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('update', $student);
        $oldValues = Arr::only($student->getAttributes(), $this->auditedFields());
        $student->update($request->validated());
        $audit->record($request->user(), 'student.updated', Student::class, $student->getKey(), $oldValues, Arr::only($student->refresh()->getAttributes(), $this->auditedFields()));

        return redirect()
            ->route('admin.students.show', $student)
            ->with('status', 'Data peserta didik berhasil diperbarui.');
    }

    public function destroy(Request $request, Student $student, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('delete', $student);
        $oldValues = Arr::only($student->getAttributes(), $this->auditedFields());

        if ($student->enrollments()->exists()) {
            $student->update(['status' => 'INACTIVE']);
            $audit->record($request->user(), 'student.updated', Student::class, $student->getKey(), $oldValues, ['status' => 'INACTIVE'], 'Akun dinonaktifkan karena memiliki histori enrollment.');

            return redirect()
                ->route('admin.students.index')
                ->with('status', 'Peserta didik memiliki histori enrollment sehingga dinonaktifkan, bukan dihapus.');
        }

        $audit->record($request->user(), 'student.deleted', Student::class, $student->getKey(), $oldValues);
        $student->delete();

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Peserta didik berhasil dihapus.');
    }

    /** @return array<int, string> */
    private function auditedFields(): array
    {
        return ['student_number', 'national_student_number', 'full_name', 'birth_place', 'birth_date', 'gender', 'address', 'status'];
    }
}
