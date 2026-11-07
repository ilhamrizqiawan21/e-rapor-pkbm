<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportStudentRequest;
use App\Http\Requests\Admin\PreviewStudentImportRequest;
use App\Models\Institution;
use App\Models\Student;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;

class StudentImportController extends Controller
{
    public function create(Request $request): View
    {
        Gate::authorize('create', Student::class);

        return view('admin.students.import.create');
    }

    public function template(StudentImportService $service)
    {
        Gate::authorize('create', Student::class);

        return $service->templateResponse();
    }

    public function preview(PreviewStudentImportRequest $request, StudentImportService $service): View
    {
        $institution = $this->institution($request);
        $preview = $service->preview($request->file('file'), $institution);
        $token = $service->storePreview($preview, $request->user(), $institution);

        $request->session()->put('student_import_preview_token', $token);

        return view('admin.students.import.preview', compact('preview', 'token'));
    }

    public function store(ImportStudentRequest $request, StudentImportService $service): RedirectResponse
    {
        $institution = $this->institution($request);
        $preview = $service->loadPreview($request->string('preview_token')->toString(), $request->user(), $institution);
        if ($preview === null) {
            throw new RuntimeException('Pratinjau sudah kedaluwarsa atau tidak dapat digunakan. Silakan unggah file kembali.');
        }

        $result = $service->import($preview, $institution);
        $service->forgetPreview($request->string('preview_token')->toString());
        $request->session()->forget('student_import_preview_token');

        $conflictMessage = $result['conflicted'] > 0
            ? " {$result['conflicted']} baris berubah setelah pratinjau dan dilewati."
            : '';

        return back()->with('status', "Impor selesai: {$result['imported']} peserta didik ditambahkan, {$result['skipped']} baris dilewati.".$conflictMessage);
    }

    public function errorReport(Request $request, StudentImportService $service)
    {
        Gate::authorize('create', Student::class);
        $token = $request->session()->get('student_import_preview_token');
        if (! is_string($token)) {
            throw new RuntimeException('Pratinjau impor tidak ditemukan.');
        }

        $preview = $service->loadPreview($token, $request->user(), $this->institution($request));
        if ($preview === null) {
            throw new RuntimeException('Pratinjau impor sudah kedaluwarsa.');
        }

        return $service->errorReportResponse($preview);
    }

    private function institution(Request $request): Institution
    {
        $institution = $request->user()?->institution;
        abort_unless($institution instanceof Institution, 403, 'Akun belum memiliki institusi.');

        return $institution;
    }
}
