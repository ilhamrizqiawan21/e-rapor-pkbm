<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectReportCardRequest;
use App\Http\Requests\Admin\UnlockReportCardRequest;
use App\Models\ReportCard;
use App\Services\ReportCardWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportCardWorkflowController extends Controller
{
    public function submit(Request $request, ReportCard $reportCard, ReportCardWorkflowService $service): RedirectResponse
    {
        Gate::authorize('submit', $reportCard);
        $service->submit($reportCard, $request->user());

        return back()->with('status', 'Rapor berhasil diajukan untuk ditinjau.');
    }

    public function review(Request $request, ReportCard $reportCard, ReportCardWorkflowService $service): RedirectResponse
    {
        Gate::authorize('review', $reportCard);
        $service->review($reportCard, $request->user());

        return back()->with('status', 'Rapor masuk ke tahap peninjauan.');
    }

    public function reject(RejectReportCardRequest $request, ReportCard $reportCard, ReportCardWorkflowService $service): RedirectResponse
    {
        Gate::authorize('reject', $reportCard);
        $service->reject($reportCard, $request->user(), $request->validated('reason'));

        return back()->with('status', 'Rapor dikembalikan untuk diperbaiki.');
    }

    public function approve(Request $request, ReportCard $reportCard, ReportCardWorkflowService $service): RedirectResponse
    {
        Gate::authorize('approve', $reportCard);
        $service->approve($reportCard, $request->user());

        return back()->with('status', 'Rapor berhasil disetujui Kepala PKBM.');
    }

    public function lock(Request $request, ReportCard $reportCard, ReportCardWorkflowService $service): RedirectResponse
    {
        Gate::authorize('lock', $reportCard);
        $service->lock($reportCard, $request->user());

        return back()->with('status', 'Rapor berhasil dikunci.');
    }

    public function unlock(UnlockReportCardRequest $request, ReportCard $reportCard, ReportCardWorkflowService $service): RedirectResponse
    {
        Gate::authorize('unlock', $reportCard);
        $service->unlock($reportCard, $request->user(), $request->validated('reason'));

        return back()->with('status', 'Rapor berhasil dibuka kembali untuk perbaikan.');
    }
}
