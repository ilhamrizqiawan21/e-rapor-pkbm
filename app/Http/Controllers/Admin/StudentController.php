<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Student::class);

        $students = Student::query()
            ->visibleTo($request->user())
            ->withCount('enrollments')
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

        return view('admin.students.index', compact('students'));
    }

    public function create(): View
    {
        Gate::authorize('create', Student::class);

        return view('admin.students.create', [
            'student' => new Student(['status' => 'ACTIVE']),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $student = Student::query()->create([
            ...$request->validated(),
            'institution_id' => $request->user()->institution_id,
        ]);

        return redirect()
            ->route('admin.students.show', $student)
            ->with('status', 'Peserta didik berhasil ditambahkan.');
    }

    public function show(Student $student): View
    {
        Gate::authorize('view', $student);

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

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        Gate::authorize('update', $student);
        $student->update($request->validated());

        return redirect()
            ->route('admin.students.show', $student)
            ->with('status', 'Data peserta didik berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        if ($student->enrollments()->exists()) {
            $student->update(['status' => 'INACTIVE']);

            return redirect()
                ->route('admin.students.index')
                ->with('status', 'Peserta didik memiliki histori enrollment sehingga dinonaktifkan, bukan dihapus.');
        }

        $student->delete();

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Peserta didik berhasil dihapus.');
    }
}
