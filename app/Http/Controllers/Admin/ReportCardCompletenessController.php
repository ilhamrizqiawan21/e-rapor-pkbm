<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHomeroomNoteRequest;
use App\Http\Requests\Admin\StoreReportExtracurricularsRequest;
use App\Models\ReportCard;
use App\Services\ReportCardExtrasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportCardCompletenessController extends Controller
{
    public function edit(Request $request, ReportCard $reportCard, ReportCardExtrasService $service): View
    {
        Gate::authorize('manageCompleteness', $reportCard);

        return view('admin.report-cards.completeness.edit', $service->formData($reportCard));
    }

    public function updateExtracurriculars(
        StoreReportExtracurricularsRequest $request,
        ReportCard $reportCard,
        ReportCardExtrasService $service,
    ): RedirectResponse {
        Gate::authorize('manageCompleteness', $reportCard);
        $service->replaceExtracurriculars($reportCard, $request->user(), $request->validated('rows', []));

        return back()->with('status', 'Data ekstrakurikuler berhasil disimpan.');
    }

    public function updateHomeroomNote(
        StoreHomeroomNoteRequest $request,
        ReportCard $reportCard,
        ReportCardExtrasService $service,
    ): RedirectResponse {
        Gate::authorize('manageCompleteness', $reportCard);
        $service->saveHomeroomNote($reportCard, $request->user(), $request->validated('note'));

        return back()->with('status', 'Catatan wali kelas berhasil disimpan.');
    }
}
