<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Http\Requests\Admin\WithdrawEnrollmentRequest;
use App\Models\AcademicClass;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\EnrollmentPlacementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function index(Request $request, AcademicClass $academicClass): View
    {
        Gate::authorize('view', $academicClass);

        $status = $request->string('status')->toString();
        $query = $academicClass->enrollments()->with('student')->latest('start_date');

        if (in_array($status, ['ACTIVE', 'WITHDRAWN', 'TRANSFERRED', 'COMPLETED', 'GRADUATED'], true)) {
            $query->where('enrollment_status', $status);
        }

        return view('admin.enrollments.index', [
            'academicClass' => $academicClass->load(['academicYear', 'program', 'level']),
            'enrollments' => $query->get(),
            'statusFilter' => $status,
        ]);
    }

    public function create(Request $request, AcademicClass $academicClass): View
    {
        Gate::authorize('create', Enrollment::class);
        Gate::authorize('view', $academicClass);
        $this->ensurePeriodOpen($academicClass);

        return view('admin.enrollments.create', [
            'academicClass' => $academicClass->load(['academicYear', 'program', 'level']),
            'enrollment' => new Enrollment(['start_date' => now()->toDateString()]),
            'students' => Student::query()
                ->where('institution_id', $request->user()->institution_id)
                ->where('status', 'ACTIVE')
                ->whereDoesntHave('enrollments', fn (Builder $query) => $query
                    ->where('academic_year_id', $academicClass->academic_year_id)
                    ->where('enrollment_status', 'ACTIVE'))
                ->orderBy('full_name')
                ->get(),
        ]);
    }

    public function store(
        StoreEnrollmentRequest $request,
        AcademicClass $academicClass,
        EnrollmentPlacementService $service,
    ): RedirectResponse {
        Gate::authorize('create', Enrollment::class);
        Gate::authorize('view', $academicClass);
        $this->ensurePeriodOpen($academicClass);

        $data = $request->validated();
        $enrollment = $service->enroll(
            Student::query()->findOrFail($data['student_id']),
            $academicClass,
            $data['start_date'],
            $data['notes'] ?? null,
            $request->user(),
        );

        return redirect()
            ->route('admin.enrollments.show', $enrollment)
            ->with('status', 'Siswa berhasil ditempatkan ke kelas.');
    }

    public function show(Enrollment $enrollment): View
    {
        $enrollment->load([
            'student',
            'academicClass.academicYear',
            'academicClass.program',
            'academicClass.level',
            'reportingEnrollment.academicClass',
        ]);
        Gate::authorize('view', $enrollment);

        $timeline = Enrollment::query()
            ->where('student_id', $enrollment->student_id)
            ->with(['academicClass.academicYear', 'academicClass.program', 'academicClass.level'])
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        return view('admin.enrollments.show', compact('enrollment', 'timeline'));
    }

    public function withdraw(
        WithdrawEnrollmentRequest $request,
        Enrollment $enrollment,
        EnrollmentPlacementService $service,
    ): RedirectResponse {
        Gate::authorize('update', $enrollment);
        $this->ensurePeriodOpen($enrollment->academicClass);

        $service->withdraw(
            $enrollment,
            $request->validated('end_date'),
            $request->validated('reason'),
            $request->user(),
        );

        return redirect()
            ->route('admin.enrollments.show', $enrollment)
            ->with('status', 'Siswa berhasil dikeluarkan dari kelas tanpa menghapus histori.');
    }

    public function destroy(
        WithdrawEnrollmentRequest $request,
        Enrollment $enrollment,
        EnrollmentPlacementService $service,
    ): RedirectResponse {
        return $this->withdraw($request, $enrollment, $service);
    }

    private function ensurePeriodOpen(AcademicClass $academicClass): void
    {
        $academicClass->loadMissing('academicYear');
        $status = $academicClass->academicYear->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;

        abort_if(
            strtoupper($statusValue) === 'CLOSED',
            422,
            'Periode akademik sudah ditutup dan tidak menerima perubahan penempatan siswa.',
        );
    }
}
