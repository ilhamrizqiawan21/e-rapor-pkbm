<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GradeEntryIndexRequest;
use App\Http\Requests\Admin\StoreGradeEntryContextRequest;
use App\Http\Requests\Admin\StoreReportGradeRequest;
use App\Http\Requests\Admin\StoreReportGradesRequest;
use App\Models\ReportCard;
use App\Services\GradeEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GradeEntryController extends Controller
{
    public function index(GradeEntryIndexRequest $request, GradeEntryService $service): View
    {
        Gate::authorize('viewAny', ReportCard::class);

        return view('admin.grade-entry.index', $service->grid($request->user(), $request->validated()));
    }

    public function store(StoreReportGradeRequest $request, GradeEntryService $service): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $grade = $service->saveRow($request->user(), $data);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Nilai berhasil disimpan.', 'grade' => $grade]);
        }

        return redirect()->back()->withInput()->with('success', 'Nilai peserta didik berhasil disimpan.');
    }

    public function storeAll(StoreReportGradesRequest $request, GradeEntryService $service): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $result = $service->saveRows($request->user(), [
            'academic_year_id' => $data['academic_year_id'],
            'semester_id' => $data['semester_id'],
            'class_id' => $data['class_id'],
            'subject_id' => $data['subject_id'] ?? null,
            'subject_assignment_id' => $data['subject_assignment_id'],
            'material' => $data['material'] ?? null,
        ], $data['rows']);

        if ($request->expectsJson()) {
            return response()->json($result, $result['errors'] === [] ? 200 : 422);
        }

        $response = redirect()->back()->withInput()->with('success', "{$result['saved']} baris nilai berhasil disimpan.");

        if ($result['errors'] !== []) {
            $response->with('grade_errors', $result['errors']);
        }

        return $response;
    }

    public function storeContext(StoreGradeEntryContextRequest $request, GradeEntryService $service): JsonResponse
    {
        $service->saveContext($request->user(), $request->validated());

        return response()->json(['message' => 'Materi pembelajaran berhasil disimpan.']);
    }
}
