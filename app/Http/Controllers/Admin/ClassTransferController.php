<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassTransferRequest;
use App\Models\AcademicClass;
use App\Models\ClassTransfer;
use App\Models\Enrollment;
use App\Services\EnrollmentPlacementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClassTransferController extends Controller
{
    public function index(Request $request, Enrollment $enrollment): View
    {
        $enrollment->load(['student', 'academicClass.program', 'academicClass.academicYear']);
        Gate::authorize('view', $enrollment);

        $yearId = $request->integer('academic_year_id') ?: $enrollment->academic_year_id;
        $timeline = Enrollment::query()
            ->where('student_id', $enrollment->student_id)
            ->when($yearId > 0, fn (Builder $query) => $query->where('academic_year_id', $yearId))
            ->with(['academicClass.program', 'academicClass.level', 'academicClass.academicYear', 'reportingEnrollment'])
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();
        $transfers = ClassTransfer::query()
            ->where('student_id', $enrollment->student_id)
            ->when($yearId > 0, fn (Builder $query) => $query
                ->whereHas('fromEnrollment', fn ($from) => $from->where('academic_year_id', $yearId)))
            ->with(['fromEnrollment.academicClass', 'toEnrollment.academicClass', 'approvedByAdmin'])
            ->latest('effective_date')
            ->get();

        return view('admin.transfers.index', [
            'enrollment' => $enrollment,
            'timeline' => $timeline,
            'transfers' => $transfers,
            'yearId' => $yearId,
        ]);
    }

    public function create(Enrollment $enrollment): View
    {
        $enrollment->load(['student', 'academicClass.program', 'academicClass.level', 'academicClass.academicYear']);
        Gate::authorize('create', ClassTransfer::class);
        Gate::authorize('view', $enrollment);
        $this->ensurePeriodOpen($enrollment->academicClass);

        return view('admin.transfers.create', [
            'enrollment' => $enrollment,
            'classes' => AcademicClass::query()
                ->where('academic_year_id', $enrollment->academic_year_id)
                ->where('id', '!=', $enrollment->class_id)
                ->whereHas('program', fn ($programs) => $programs->where('institution_id', request()->user()->institution_id))
                ->where('status', 'ACTIVE')
                ->with(['program', 'level'])
                ->orderBy('program_id')
                ->orderBy('level_id')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(
        StoreClassTransferRequest $request,
        Enrollment $enrollment,
        EnrollmentPlacementService $service,
    ): RedirectResponse {
        Gate::authorize('create', ClassTransfer::class);
        Gate::authorize('view', $enrollment);
        $this->ensurePeriodOpen($enrollment->academicClass);

        $data = $request->validated();
        $transfer = $service->transfer(
            $enrollment,
            AcademicClass::query()->findOrFail($data['target_class_id']),
            $data['effective_date'],
            $data['reason'],
            $data['notes'] ?? null,
            $request->user(),
            $data['reporting_enrollment_id'] ?? 'target',
        );

        return redirect()
            ->route('admin.enrollments.transfers.index', $transfer->to_enrollment_id)
            ->with('status', 'Perpindahan kelas berhasil disetujui dan dicatat.');
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
