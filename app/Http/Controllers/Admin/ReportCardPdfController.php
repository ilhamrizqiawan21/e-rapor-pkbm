<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PdfEngineUnavailable;
use App\Exceptions\ReportCardPdfException;
use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\ReportCard;
use App\Models\Semester;
use App\Services\Reports\ReportCardPdfDataService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ReportCardPdfController extends Controller
{
    public function preview(ReportCard $reportCard, ReportCardPdfDataService $pdf): View|Response
    {
        Gate::authorize('view', $reportCard);

        try {
            return response()->view('reports.report-card.pdf', $pdf->data($reportCard), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        } catch (ReportCardPdfException $exception) {
            return $this->failure($exception, 422);
        }
    }

    public function download(ReportCard $reportCard, ReportCardPdfDataService $pdf): Response
    {
        return $this->single($reportCard, $pdf, 'attachment');
    }

    public function print(ReportCard $reportCard, ReportCardPdfDataService $pdf): Response
    {
        return $this->single($reportCard, $pdf, 'inline');
    }

    public function batch(
        Request $request,
        AcademicClass $academicClass,
        Semester $semester,
        ReportCardPdfDataService $pdf,
    ): BinaryFileResponse|Response {
        Gate::authorize('viewAny', ReportCard::class);
        $academicClass->loadMissing('program');
        $semester->loadMissing('academicYear');

        abort_unless(
            $academicClass->program?->institution_id === $request->user()->institution_id
                && $semester->academicYear?->institution_id === $request->user()->institution_id
                && $academicClass->academic_year_id === $semester->academic_year_id,
            403,
        );

        try {
            $archive = $pdf->batchArchive($academicClass, $semester);

            return response()->download($archive['path'], $archive['filename'], [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (PdfEngineUnavailable $exception) {
            return $this->failure($exception, 503);
        } catch (ReportCardPdfException $exception) {
            return $this->failure($exception, 422);
        }
    }

    private function single(ReportCard $reportCard, ReportCardPdfDataService $pdf, string $disposition): Response
    {
        Gate::authorize('view', $reportCard);

        try {
            $filename = $pdf->filename($reportCard);
            $contents = $pdf->pdf($reportCard);

            return response($contents, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
                'Content-Length' => (string) strlen($contents),
            ]);
        } catch (PdfEngineUnavailable $exception) {
            return $this->failure($exception, 503);
        } catch (ReportCardPdfException $exception) {
            return $this->failure($exception, 422);
        }
    }

    private function failure(\Throwable $exception, int $status): Response
    {
        return response($exception->getMessage(), $status, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
