<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SavePeriodPlacementsRequest;
use App\Http\Requests\SavePeriodPreparationRequest;
use App\Models\AcademicYear;
use App\Models\PeriodPreparation;
use App\Services\AcademicRolloverService;
use App\Services\PeriodReadinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicRolloverController extends Controller
{
    public function index(Request $request, AcademicYear $academicYear, AcademicRolloverService $service, PeriodReadinessService $readiness): View
    {
        $service->authorize($request->user(), $academicYear, false);
        $preparation = $service->preparation($academicYear);
        $state = $readiness->evaluate($academicYear, $preparation);

        return view('admin.academic-years.transition', ['academicYear' => $academicYear, 'preparation' => $preparation,
            'state' => $state, 'suggested' => $service->suggestedYear($academicYear), 'step' => min(3, max(1, $request->integer('step', $preparation ? 2 : 1))),
            'canManage' => $request->user()->hasRole('ADMIN')]);
    }

    public function prepare(SavePeriodPreparationRequest $request, AcademicYear $academicYear, AcademicRolloverService $service): RedirectResponse
    {
        $service->prepare($request->user(), $academicYear, $request->validated());

        return redirect()->route('admin.academic-years.transition', [$academicYear, 'step' => 2])->with('status', 'Tahun tujuan dan kedua semester disiapkan sebagai Draf.');
    }

    public function placements(SavePeriodPlacementsRequest $request, AcademicYear $academicYear, AcademicRolloverService $service): RedirectResponse
    {
        $service->savePlacements($request->user(), $academicYear, $request->validated());
        if ($request->input('intent') === 'review') {
            $service->review($request->user(), $academicYear);

            return redirect()->route('admin.academic-years.transition', [$academicYear, 'step' => 3]);
        }

        return redirect()->route('admin.academic-years.transition', [$academicYear, 'step' => 2])->with('status', 'Penempatan tujuan tersimpan. Persiapan dapat dilanjutkan kapan saja.');
    }

    public function preview(Request $request, AcademicYear $academicYear, AcademicRolloverService $service): RedirectResponse
    {
        $request->validate(['rows' => ['prohibited'], 'decision' => ['prohibited']]);
        $service->review($request->user(), $academicYear);

        return redirect()->route('admin.academic-years.transition', [$academicYear, 'step' => 3]);
    }

    public function store(Request $request, AcademicYear $academicYear, AcademicRolloverService $service): RedirectResponse
    {
        $request->validate(['fingerprint' => ['required', 'string', 'size:64'], 'confirm' => ['accepted'], 'rows' => ['prohibited'], 'decision' => ['prohibited']]);
        $service->activate($request->user(), $academicYear, $request->string('fingerprint')->toString());
        $target = PeriodPreparation::query()->where('source_year_id', $academicYear->id)->where('status', 'COMPLETED')->latest('completed_at')->firstOrFail();

        return redirect()->route('admin.academic-years.show', $target->target_year_id)->with('status', 'Periode baru berhasil dimulai. Arsip periode lama tetap tersimpan.');
    }

    public function copy(Request $request, AcademicYear $academicYear, AcademicRolloverService $service): RedirectResponse
    {
        $service->authorize($request->user(), $academicYear);
        $preparation = $service->preparation($academicYear);
        abort_unless($preparation?->targetYear, 422, 'Siapkan tahun tujuan terlebih dahulu.');
        $service->copyClasses($request->user(), $academicYear, $preparation->targetYear);

        return redirect()->route('admin.academic-years.transition', [$academicYear, 'step' => 2])->with('status', 'Struktur kelas disalin. Kelas yang sudah ada tetap dipertahankan.');
    }
}
