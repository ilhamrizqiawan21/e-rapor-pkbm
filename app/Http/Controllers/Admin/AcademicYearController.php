<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcademicYearStatus;
use App\Enums\SemesterCode;
use App\Enums\SemesterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicYearRequest;
use App\Http\Requests\Admin\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Services\AcademicPeriodService;
use App\Services\PeriodReadinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(Request $request, PeriodReadinessService $readiness): View
    {
        Gate::authorize('viewAny', AcademicYear::class);

        $query = AcademicYear::query()
            ->where('institution_id', $request->user()->institution_id);

        if ($request->user()->hasRole('GURU')) {
            $query->whereHas('classes', function ($classes) use ($request): void {
                $classes->where('homeroom_teacher_id', $request->user()->getKey())
                    ->orWhereHas('subjectAssignments', fn ($assignments) => $assignments->where('teacher_id', $request->user()->getKey()));
            });
        }

        $academicYears = $query
            ->withCount(['semesters', 'classes'])
            ->with(['semesters' => fn ($query) => $query->orderBy('sequence')])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        return view('admin.academic-years.index', [
            'academicYears' => $academicYears,
            'periodActions' => $request->user()->hasAnyRole(['ADMIN', 'KEPSEK'])
                ? $academicYears->where('status', AcademicYearStatus::Active)->mapWithKeys(fn ($year) => [$year->id => $readiness->evaluate($year)])->all() : [],
            'statusOptions' => AcademicYearStatus::cases(),
            'modalAcademicYear' => new AcademicYear(['status' => AcademicYearStatus::Draft]),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', AcademicYear::class);

        return view('admin.academic-years.create', [
            'academicYear' => new AcademicYear(['status' => AcademicYearStatus::Draft]),
            'statusOptions' => AcademicYearStatus::cases(),
        ]);
    }

    public function store(StoreAcademicYearRequest $request, AcademicPeriodService $service): RedirectResponse
    {
        $academicYear = $service->createAcademicYear(
            $request->user()->institution_id,
            $request->validated(),
        );

        return redirect()->route('admin.academic-years.show', $academicYear)
            ->with('status', 'Tahun pelajaran berhasil dibuat.');
    }

    public function show(AcademicYear $academicYear, PeriodReadinessService $readiness): View
    {
        Gate::authorize('view', $academicYear);
        $academicYear->load(['semesters' => fn ($query) => $query->orderBy('sequence')]);

        return view('admin.academic-years.show', [
            'academicYear' => $academicYear,
            'actionState' => auth()->user()->hasAnyRole(['ADMIN', 'KEPSEK']) && $academicYear->status === AcademicYearStatus::Active ? $readiness->evaluate($academicYear) : null,
            'modalSemester' => new Semester([
                'code' => SemesterCode::Ganjil,
                'name' => SemesterCode::Ganjil->label(),
                'sequence' => 1,
                'status' => SemesterStatus::Draft,
            ]),
            'semesterCodeOptions' => SemesterCode::cases(),
            'semesterStatusOptions' => SemesterStatus::cases(),
        ]);
    }

    public function edit(AcademicYear $academicYear): View
    {
        Gate::authorize('update', $academicYear);

        return view('admin.academic-years.edit', [
            'academicYear' => $academicYear,
            'statusOptions' => AcademicYearStatus::cases(),
        ]);
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear, AcademicPeriodService $service): RedirectResponse
    {
        $service->updateAcademicYear($academicYear, $request->validated());

        return redirect()->route('admin.academic-years.show', $academicYear)
            ->with('status', 'Tahun pelajaran berhasil diperbarui.');
    }

    public function destroy(AcademicYear $academicYear, AcademicPeriodService $service): RedirectResponse
    {
        Gate::authorize('delete', $academicYear);
        $service->deleteAcademicYear($academicYear);

        return redirect()->route('admin.academic-years.index')
            ->with('status', 'Tahun pelajaran berhasil dihapus.');
    }
}
