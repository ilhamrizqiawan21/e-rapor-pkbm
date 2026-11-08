<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PdfEngineUnavailable;
use App\Exceptions\ReportCardPdfException;
use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\ReportCard;
use App\Models\Semester;
use App\Services\AuditLogger;
use App\Services\Reports\ReportCardPdfDataService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ReportCardPdfController extends Controller
{
    public function preview(Request $request, ReportCard $reportCard, ReportCardPdfDataService $pdf, AuditLogger $audit): View|Response
    {
        Gate::authorize('export', $reportCard);
        $audit->record($request->user(), 'report_card.viewed', ReportCard::class, $reportCard->getKey());

        try {
            return response()->view('reports.report-card.pdf', $pdf->data($reportCard), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        } catch (ReportCardPdfException $exception) {
            return $this->failure($exception, 422);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->failure($exception, 500);
        }
    }

    public function download(Request $request, ReportCard $reportCard, ReportCardPdfDataService $pdf, AuditLogger $audit): Response
    {
        return $this->single($request, $reportCard, $pdf, 'attachment', $audit);
    }

    public function print(Request $request, ReportCard $reportCard, ReportCardPdfDataService $pdf, AuditLogger $audit): Response
    {
        return $this->single($request, $reportCard, $pdf, 'inline', $audit);
    }

    public function identity(Request $request, ReportCard $reportCard, ReportCardPdfDataService $pdf, AuditLogger $audit): Response
    {
        return $this->extract($request, $reportCard, $pdf, 'identitas', $audit);
    }

    public function grades(Request $request, ReportCard $reportCard, ReportCardPdfDataService $pdf, AuditLogger $audit): Response
    {
        return $this->extract($request, $reportCard, $pdf, 'nilai', $audit);
    }

    public function batch(
        Request $request,
        AcademicClass $academicClass,
        Semester $semester,
        ReportCardPdfDataService $pdf,
        AuditLogger $audit,
    ): BinaryFileResponse|Response {
        Gate::authorize('viewAny', ReportCard::class);
        $academicClass->loadMissing('program');
        $semester->loadMissing('academicYear');

        if ($request->user()->hasRole('GURU')) {
            abort_unless($academicClass->homeroom_teacher_id === $request->user()->getKey(), 403);
        }

        abort_unless(
            $academicClass->program?->institution_id === $request->user()->institution_id
                && $semester->academicYear?->institution_id === $request->user()->institution_id
                && $academicClass->academic_year_id === $semester->academic_year_id,
            403,
        );

        try {
            $archive = $pdf->batchArchive($academicClass, $semester);
            $audit->record($request->user(), 'report_card.exported', AcademicClass::class, $academicClass->getKey(), null, [
                'format' => 'pdf-zip',
                'filename' => $archive['filename'],
                'semester_id' => $semester->getKey(),
            ]);

            return response()->download($archive['path'], $archive['filename'], [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (PdfEngineUnavailable $exception) {
            return $this->failure($exception, 503);
        } catch (ReportCardPdfException $exception) {
            return $this->failure($exception, 422);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->failure($exception, 500);
        }
    }

    private function single(Request $request, ReportCard $reportCard, ReportCardPdfDataService $pdf, string $disposition, AuditLogger $audit): Response
    {
        Gate::authorize('export', $reportCard);

        try {
            $filename = $pdf->filename($reportCard);
            $contents = $pdf->pdf($reportCard);

            $audit->record($request->user(), 'report_card.exported', ReportCard::class, $reportCard->getKey(), null, [
                'format' => 'pdf',
                'disposition' => $disposition,
                'filename' => $filename,
            ]);

            return response($contents, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
                'Content-Length' => (string) strlen($contents),
            ]);
        } catch (PdfEngineUnavailable $exception) {
            return $this->failure($exception, 503);
        } catch (ReportCardPdfException $exception) {
            return $this->failure($exception, 422);
        } catch (\Throwable $exception) {
            return $this->failure($exception, 500);
        }
    }

    private function extract(Request $request, ReportCard $reportCard, ReportCardPdfDataService $pdf, string $suffix, AuditLogger $audit): Response
    {
        Gate::authorize('export', $reportCard);

        try {
            $contents = $suffix === 'identitas'
                ? $pdf->identityPdf($reportCard)
                : $pdf->gradesPdf($reportCard);
            $filename = str_replace('.pdf', '-'.$suffix.'.pdf', $pdf->filename($reportCard));
            $audit->record($request->user(), 'report_card.exported', ReportCard::class, $reportCard->getKey(), null, [
                'format' => $suffix.'-pdf',
                'filename' => $filename,
            ]);

            $disposition = $request->boolean('preview') ? 'inline' : 'attachment';

            return response($contents, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
                'Content-Length' => (string) strlen($contents),
            ]);
        } catch (PdfEngineUnavailable $exception) {
            return $this->failure($exception, 503);
        } catch (ReportCardPdfException $exception) {
            return $this->failure($exception, 422);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->failure($exception, 500);
        }
    }

    private function failure(\Throwable $exception, int $status): Response
    {
        report($exception);

        return response('Dokumen rapor belum dapat diproses. Hubungi Admin jika masalah berlanjut.', $status, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
