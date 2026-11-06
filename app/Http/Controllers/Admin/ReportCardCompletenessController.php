<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHomeroomNoteRequest;
use App\Http\Requests\Admin\StoreReportExtracurricularsRequest;
use App\Models\ReportCard;
use App\Services\ReportCardCompletenessService;
use App\Services\ReportCardExtrasService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportCardCompletenessController extends Controller
{
    public function index(Request $request, ReportCardCompletenessService $completeness): View
    {
        Gate::authorize('viewAny', ReportCard::class);

        $user = $request->user();
        $reportCards = ReportCard::query()
            ->with([
                'enrollment.student',
                'enrollment.academicClass.program',
                'semester.academicYear',
            ])
            ->whereHas('enrollment.academicClass.program', fn (Builder $query) => $query->where('institution_id', $user->institution_id))
            ->when($user->hasRole('GURU'), function (Builder $query) use ($user): void {
                $query->whereHas('enrollment.academicClass', function (Builder $classQuery) use ($user): void {
                    $classQuery
                        ->where('homeroom_teacher_id', $user->getKey())
                        ->orWhereHas('subjectAssignments', fn (Builder $assignmentQuery) => $assignmentQuery->where('teacher_id', $user->getKey()));
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $progress = $reportCards->getCollection()->mapWithKeys(
            fn (ReportCard $reportCard): array => [$reportCard->getKey() => $completeness->summary($reportCard)],
        );

        return view('admin.report-cards.index', compact('reportCards', 'progress'));
    }

    public function edit(ReportCard $reportCard, ReportCardExtrasService $service, ReportCardCompletenessService $completeness): View
    {
        Gate::authorize('view', $reportCard);

        return view('admin.report-cards.completeness.edit', [
            ...$service->formData($reportCard),
            'progress' => $completeness->summary($reportCard),
        ]);
    }

    public function updateExtracurriculars(
        StoreReportExtracurricularsRequest $request,
        ReportCard $reportCard,
        ReportCardExtrasService $service,
    ): RedirectResponse {
        Gate::authorize('manageCompleteness', $reportCard);
        $service->replaceExtracurriculars($reportCard, $request->user(), $request->validated('rows', []));

        return back()->with('status', 'Data ekstrakurikuler berhasil disimpan.');
    }

    public function updateHomeroomNote(
        StoreHomeroomNoteRequest $request,
        ReportCard $reportCard,
        ReportCardExtrasService $service,
    ): RedirectResponse {
        Gate::authorize('manageCompleteness', $reportCard);
        $service->saveHomeroomNote($reportCard, $request->user(), $request->validated('note'));

        return back()->with('status', 'Catatan wali kelas berhasil disimpan.');
    }
}
