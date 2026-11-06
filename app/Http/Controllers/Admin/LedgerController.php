<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\LedgerExportException;
use App\Exceptions\PdfEngineUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LedgerExportRequest;
use App\Models\AcademicClass;
use App\Models\Semester;
use App\Services\Reports\LedgerExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class LedgerController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['ADMIN', 'GURU', 'KEPSEK']), 403);
        $semesters = Semester::query()
            ->with('academicYear')
            ->whereHas('academicYear', fn (Builder $query): Builder => $query->where('institution_id', $user->institution_id))
            ->orderByDesc('academic_year_id')
            ->orderBy('sequence')
            ->get();
        $classes = AcademicClass::query()
            ->with(['program', 'level', 'academicYear'])
            ->whereHas('program', fn (Builder $query): Builder => $query->where('institution_id', $user->institution_id))
            ->when($user->hasRole('GURU'), function (Builder $query) use ($user): void {
                $query->where(function (Builder $class) use ($user): void {
                    $class
                        ->where('homeroom_teacher_id', $user->getKey())
                        ->orWhereHas('subjectAssignments', fn (Builder $assignment): Builder => $assignment->where('teacher_id', $user->getKey()));
                });
            })
            ->orderBy('academic_year_id')
            ->orderBy('code')
            ->orderBy('name')
            ->get()
            ->groupBy('academic_year_id');

        return view('admin.ledger.index', compact('semesters', 'classes'));
    }

    public function classExcel(LedgerExportRequest $request, AcademicClass $academicClass, Semester $semester, LedgerExportService $ledger): BinaryFileResponse|Response
    {
        try {
            $export = $ledger->excel($request->user(), $semester, $academicClass);

            return response()->download($export['path'], $export['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (LedgerExportException|PdfEngineUnavailable $exception) {
            return response($this->errorMessage($exception), 422, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
    }

    public function allExcel(LedgerExportRequest $request, Semester $semester, LedgerExportService $ledger): BinaryFileResponse|Response
    {
        try {
            $export = $ledger->excel($request->user(), $semester);

            return response()->download($export['path'], $export['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (LedgerExportException|PdfEngineUnavailable $exception) {
            return response($this->errorMessage($exception), 422, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
    }

    public function classPdf(LedgerExportRequest $request, AcademicClass $academicClass, Semester $semester, LedgerExportService $ledger): Response
    {
        return $this->pdf($request, $semester, $academicClass, $ledger);
    }

    public function allPdf(LedgerExportRequest $request, Semester $semester, LedgerExportService $ledger): Response
    {
        return $this->pdf($request, $semester, null, $ledger);
    }

    private function pdf(LedgerExportRequest $request, Semester $semester, ?AcademicClass $academicClass, LedgerExportService $ledger): Response
    {
        try {
            $export = $ledger->pdf($request->user(), $semester, $academicClass);

            return response($export['contents'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$export['filename'].'"',
                'Content-Length' => (string) strlen($export['contents']),
            ]);
        } catch (LedgerExportException|PdfEngineUnavailable $exception) {
            return response($this->errorMessage($exception), 422, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
    }

    private function errorMessage(\Throwable $exception): string
    {
        return config('app.debug')
            ? $exception->getMessage()
            : 'Export belum dapat diproses. Hubungi Admin jika masalah berlanjut.';
    }
}
