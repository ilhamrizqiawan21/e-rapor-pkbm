<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceSummaryRequest;
use App\Http\Requests\Admin\StoreAttitudeAssessmentRequest;
use App\Models\ReportCard;
use App\Services\ReportCardAssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportCardAssessmentController extends Controller
{
    public function show(Request $request, ReportCard $reportCard, ReportCardAssessmentService $service): View
    {
        return view('admin.report-cards.assessments.show', $service->surface($reportCard, $request->user()));
    }

    public function updateAttitude(StoreAttitudeAssessmentRequest $request, ReportCard $reportCard, ReportCardAssessmentService $service): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $assessment = $service->saveAttitude($reportCard, $request->user(), $data['attitude_type'], $data['description'] ?? null);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Deskripsi sikap berhasil disimpan.', 'assessment' => $assessment]);
        }

        return redirect()->back()->with('status', 'Deskripsi sikap berhasil disimpan.');
    }

    public function updateAttendance(StoreAttendanceSummaryRequest $request, ReportCard $reportCard, ReportCardAssessmentService $service): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $summary = $service->saveAttendance($reportCard, $request->user(), [
            'sick_days' => (int) $data['sick_days'],
            'leave_days' => (int) $data['leave_days'],
            'unexcused_days' => (int) $data['unexcused_days'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Rekap absensi berhasil disimpan.', 'attendance' => $summary]);
        }

        return redirect()->back()->with('status', 'Rekap absensi berhasil disimpan.');
    }
}
