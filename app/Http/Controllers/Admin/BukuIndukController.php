<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PdfEngineUnavailable;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Student;
use App\Services\AuditLogger;
use App\Services\Reports\BukuIndukService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class BukuIndukController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Student::class);

        $query = Student::query()
            ->visibleTo($request->user())
            ->with([
                'institution',
                'enrollments' => fn ($q) => $q->with('academicClass.program', 'academicClass.level')->latest('start_date'),
            ]);

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function (Builder $q) use ($search): void {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%")
                    ->orWhere('national_student_number', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = strtoupper($request->string('status')->toString());
            if (in_array($status, ['ACTIVE', 'INACTIVE', 'GRADUATED', 'TRANSFERRED'], true)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('gender')) {
            $gender = strtoupper($request->string('gender')->toString());
            if (in_array($gender, ['L', 'P'], true)) {
                $query->where('gender', $gender);
            }
        }

        $students = $query
            ->orderBy('student_number')
            ->orderBy('full_name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.buku-induk.index', compact('students'));
    }

    public function show(Student $student, Request $request, BukuIndukService $bukuInduk, AuditLogger $audit): View
    {
        Gate::authorize('view', $student);
        $audit->record($request->user(), 'buku_induk.viewed', Student::class, $student->getKey());

        $data = $bukuInduk->studentData($student);

        return view('admin.buku-induk.show', compact('data', 'student'));
    }

    public function sheetPdf(Student $student, Request $request, BukuIndukService $bukuInduk, AuditLogger $audit): Response|View
    {
        Gate::authorize('view', $student);
        $audit->record($request->user(), 'buku_induk.sheet_pdf', Student::class, $student->getKey());

        $filename = 'buku-induk-'.Str::slug($student->full_name).'-'.$student->student_number.'.pdf';
        $data = $bukuInduk->studentData($student);

        try {
            $contents = $bukuInduk->sheetPdf($student);

            return response($contents, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        } catch (PdfEngineUnavailable) {
            return response()->view('reports.buku-induk.sheet-pdf', array_merge($data, ['autoPrint' => true]));
        }
    }

    public function sheetPrint(Student $student, Request $request, BukuIndukService $bukuInduk, AuditLogger $audit): View
    {
        Gate::authorize('view', $student);
        $audit->record($request->user(), 'buku_induk.sheet_print', Student::class, $student->getKey());

        $data = $bukuInduk->studentData($student);

        return view('reports.buku-induk.sheet-pdf', array_merge($data, ['autoPrint' => true]));
    }

    public function exportExcel(Request $request, BukuIndukService $bukuInduk, AuditLogger $audit): BinaryFileResponse
    {
        Gate::authorize('viewAny', Student::class);

        /** @var Institution $institution */
        $institution = $request->user()->institution;

        $query = Student::query()->visibleTo($request->user());

        if ($request->filled('status')) {
            $status = strtoupper($request->string('status')->toString());
            if (in_array($status, ['ACTIVE', 'INACTIVE', 'GRADUATED', 'TRANSFERRED'], true)) {
                $query->where('status', $status);
            }
        }

        $students = $query->orderBy('student_number')->orderBy('full_name')->get();

        $export = $bukuInduk->exportExcel($institution, $students);
        $audit->record($request->user(), 'buku_induk.export_excel', Institution::class, $institution->getKey());

        return response()->download($export['path'], $export['filename'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function registerPdf(Request $request, BukuIndukService $bukuInduk, AuditLogger $audit): Response|View
    {
        Gate::authorize('viewAny', Student::class);

        /** @var Institution $institution */
        $institution = $request->user()->institution;

        $query = Student::query()->visibleTo($request->user());
        if ($request->filled('status')) {
            $status = strtoupper($request->string('status')->toString());
            if (in_array($status, ['ACTIVE', 'INACTIVE', 'GRADUATED', 'TRANSFERRED'], true)) {
                $query->where('status', $status);
            }
        }
        $students = $query->orderBy('student_number')->orderBy('full_name')->get();

        $filename = 'buku-register-induk-'.Str::slug($institution->name).'.pdf';

        try {
            $contents = $bukuInduk->registerPdf($institution, $students);
            $audit->record($request->user(), 'buku_induk.register_pdf', Institution::class, $institution->getKey());

            return response($contents, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        } catch (PdfEngineUnavailable) {
            return response()->view('reports.buku-induk.register-pdf', [
                'institution' => $institution,
                'logoDataUri' => null,
                'students' => $students,
                'autoPrint' => true,
            ]);
        }
    }
}
