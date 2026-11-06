<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcademicYearStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicYearRequest;
use App\Http\Requests\Admin\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use App\Services\AcademicPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AcademicYear::class);

        $academicYears = AcademicYear::query()
            ->where('institution_id', $request->user()->institution_id)
            ->withCount(['semesters', 'classes'])
            ->with(['semesters' => fn ($query) => $query->orderBy('sequence')])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        return view('admin.academic-years.index', [
            'academicYears' => $academicYears,
            'statusOptions' => AcademicYearStatus::cases(),
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

    public function show(AcademicYear $academicYear): View
    {
        Gate::authorize('view', $academicYear);
        $academicYear->load(['semesters' => fn ($query) => $query->orderBy('sequence')]);

        return view('admin.academic-years.show', compact('academicYear'));
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
