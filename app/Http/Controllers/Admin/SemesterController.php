<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SemesterCode;
use App\Enums\SemesterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSemesterRequest;
use App\Http\Requests\Admin\UpdateSemesterRequest;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Services\AcademicPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SemesterController extends Controller
{
    public function index(AcademicYear $academicYear): View
    {
        Gate::authorize('view', $academicYear);

        return view('admin.semesters.index', [
            'academicYear' => $academicYear,
            'semesters' => $academicYear->semesters()->orderBy('sequence')->get(),
        ]);
    }

    public function create(AcademicYear $academicYear): View
    {
        Gate::authorize('create', [Semester::class, $academicYear]);

        return view('admin.semesters.create', [
            'academicYear' => $academicYear,
            'semester' => new Semester([
                'code' => SemesterCode::Ganjil,
                'name' => SemesterCode::Ganjil->label(),
                'sequence' => 1,
                'status' => SemesterStatus::Draft,
            ]),
            'codeOptions' => SemesterCode::cases(),
            'statusOptions' => SemesterStatus::cases(),
        ]);
    }

    public function store(StoreSemesterRequest $request, AcademicYear $academicYear, AcademicPeriodService $service): RedirectResponse
    {
        $service->createSemester($academicYear, $request->validated());

        return redirect()->route('admin.academic-years.show', $academicYear)
            ->with('status', 'Semester berhasil dibuat.');
    }

    public function edit(AcademicYear $academicYear, Semester $semester): View
    {
        Gate::authorize('view', $academicYear);
        abort_unless($semester->academic_year_id === $academicYear->getKey(), 404);
        Gate::authorize('update', $semester);

        return view('admin.semesters.edit', [
            'academicYear' => $academicYear,
            'semester' => $semester,
            'codeOptions' => SemesterCode::cases(),
            'statusOptions' => SemesterStatus::cases(),
        ]);
    }

    public function update(UpdateSemesterRequest $request, AcademicYear $academicYear, Semester $semester, AcademicPeriodService $service): RedirectResponse
    {
        abort_unless($semester->academic_year_id === $academicYear->getKey(), 404);
        $service->updateSemester($semester, $request->validated());

        return redirect()->route('admin.academic-years.show', $academicYear)
            ->with('status', 'Semester berhasil diperbarui.');
    }

    public function destroy(AcademicYear $academicYear, Semester $semester, AcademicPeriodService $service): RedirectResponse
    {
        abort_unless($semester->academic_year_id === $academicYear->getKey(), 404);
        Gate::authorize('delete', $semester);
        $service->deleteSemester($semester);

        return redirect()->route('admin.academic-years.show', $academicYear)
            ->with('status', 'Semester berhasil dihapus.');
    }
}
