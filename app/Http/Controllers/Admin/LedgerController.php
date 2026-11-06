<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\LedgerExportException;
use App\Exceptions\PdfEngineUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LedgerExportRequest;
use App\Models\AcademicClass;
use App\Models\Semester;
use App\Services\Reports\LedgerExportService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class LedgerController extends Controller
{
    public function classExcel(LedgerExportRequest $request, AcademicClass $academicClass, Semester $semester, LedgerExportService $ledger): BinaryFileResponse|Response
    {
        try {
            $export = $ledger->excel($request->user(), $semester, $academicClass);

            return response()->download($export['path'], $export['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (LedgerExportException|PdfEngineUnavailable $exception) {
            return response($exception->getMessage(), 422, ['Content-Type' => 'text/plain; charset=UTF-8']);
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
            return response($exception->getMessage(), 422, ['Content-Type' => 'text/plain; charset=UTF-8']);
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
            return response($exception->getMessage(), 422, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
    }
}
