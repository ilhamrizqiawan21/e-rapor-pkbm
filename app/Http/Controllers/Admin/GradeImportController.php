<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GradeImportContextRequest;
use App\Http\Requests\Admin\PreviewGradeImportRequest;
use App\Http\Requests\Admin\StoreGradeImportRequest;
use App\Models\ReportCard;
use App\Services\GradeImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;

class GradeImportController extends Controller
{
    public function create(GradeImportContextRequest $request, GradeImportService $service): View
    {
        Gate::authorize('viewAny', ReportCard::class);
        try {
            $context = $service->resolveContext($request->user(), $request->validated());
        } catch (RuntimeException $exception) {
            abort(403, $exception->getMessage());
        }

        return view('admin.grade-entry.import.create', compact('context'));
    }

    public function template(GradeImportContextRequest $request, GradeImportService $service)
    {
        Gate::authorize('viewAny', ReportCard::class);

        try {
            return $service->templateResponse($request->user(), $request->validated());
        } catch (RuntimeException $exception) {
            abort(403, $exception->getMessage());
        }
    }

    public function preview(PreviewGradeImportRequest $request, GradeImportService $service): View|RedirectResponse
    {
        try {
            $preview = $service->preview($request->user(), $request->file('file'), $request->validated());
            $batch = $service->storePreview($request->user(), $preview);

            return view('admin.grade-entry.import.preview', compact('preview', 'batch'));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['file' => $exception->getMessage()]);
        }
    }

    public function store(StoreGradeImportRequest $request, GradeImportService $service): RedirectResponse
    {
        $batch = $service->loadPreview($request->user(), $request->validated('token'));
        if ($batch === null) {
            return redirect()->route('admin.grade-entry.index')->withErrors(['file' => 'Preview impor tidak ditemukan atau sudah kedaluwarsa.']);
        }

        try {
            $imported = $service->import($request->user(), $batch);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        return redirect()->route('admin.grade-entry.index', [
            'academic_year_id' => $batch->academic_year_id,
            'semester_id' => $batch->semester_id,
            'class_id' => $batch->academic_class_id,
            'subject_id' => $batch->subjectAssignment->subject_id,
            'subject_assignment_id' => $batch->subject_assignment_id,
        ])->with('status', "{$imported} nilai berhasil diimpor. Predikat dan deskripsi dibuat otomatis oleh sistem.");
    }

    public function errors(StoreGradeImportRequest $request, GradeImportService $service)
    {
        $batch = $service->loadPreview($request->user(), $request->validated('token'));
        if ($batch === null) {
            abort(404);
        }

        return $service->errorReportResponse($batch->preview_json);
    }
}
