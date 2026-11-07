<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UnlockReportCardRequest;
use App\Models\ReportCard;
use App\Services\ReportCardWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportCardWorkflowController extends Controller
{
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
