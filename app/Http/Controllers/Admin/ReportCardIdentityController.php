<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReportCardIdentityRequest;
use App\Models\ReportCard;
use App\Services\ReportCardIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportCardIdentityController extends Controller
{
    public function edit(ReportCard $reportCard, ReportCardIdentityService $identity): View
    {
        Gate::authorize('view', $reportCard);

        return view('admin.report-cards.identity.edit', [
            'reportCard' => $reportCard,
            'context' => $identity->context($reportCard),
        ]);
    }

    public function update(UpdateReportCardIdentityRequest $request, ReportCard $reportCard, ReportCardIdentityService $identity): RedirectResponse
    {
        $identity->updateDocumentNumber($reportCard, $request->validated('document_number'), $request->user());

        return redirect()->back()->with('status', 'Nomor dokumen rapor berhasil disimpan.');
    }
}
