<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHomeroomNoteRequest;
use App\Http\Requests\Admin\StoreReportExtracurricularsRequest;
use App\Models\AcademicClass;
use App\Models\ReportCard;
use App\Models\Semester;
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
        $filters = $request->only(['academic_year_id', 'semester_id', 'class_id', 'search']);
        $reportCards = ReportCard::query()
            ->with([
                'enrollment.student',
                'enrollment.academicClass.program',
                'semester.academicYear',
            ])
            ->whereHas('enrollment.academicClass.program', fn (Builder $query) => $query->where('institution_id', $user->institution_id))
            ->when($request->filled('academic_year_id'), fn (Builder $query) => $query->whereHas('semester.academicYear', fn (Builder $yearQuery) => $yearQuery->whereKey($request->integer('academic_year_id'))))
            ->when($request->filled('semester_id'), fn (Builder $query) => $query->where('semester_id', $request->integer('semester_id')))
            ->when($request->filled('class_id'), fn (Builder $query) => $query->whereHas('enrollment', fn (Builder $enrollmentQuery) => $enrollmentQuery->where('class_id', $request->integer('class_id'))))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->whereHas('enrollment.student', fn (Builder $studentQuery) => $studentQuery->where('full_name', 'like', "%{$search}%"));
            })
            ->when($user->hasRole('GURU'), function (Builder $query) use ($user): void {
                $query->whereHas('enrollment.academicClass', function (Builder $classQuery) use ($user): void {
                    $classQuery->where('homeroom_teacher_id', $user->getKey());
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $progress = $reportCards->getCollection()->mapWithKeys(
            fn (ReportCard $reportCard): array => [$reportCard->getKey() => $completeness->summary($reportCard)],
        );

        $semesters = Semester::query()
            ->with('academicYear')
            ->whereHas('academicYear', fn (Builder $query) => $query->where('institution_id', $user->institution_id))
            ->orderByDesc('academic_year_id')
            ->orderBy('sequence')
            ->get();

        $academicYears = $semesters->pluck('academicYear')->filter()->unique('id')->values();

        $classes = AcademicClass::query()
            ->with('program')
            ->whereHas('academicYear', fn (Builder $query) => $query->where('institution_id', $user->institution_id))
            ->when($request->filled('academic_year_id'), fn (Builder $query) => $query->where('academic_year_id', $request->integer('academic_year_id')))
            ->when($user->hasRole('GURU'), fn (Builder $query) => $query->where('homeroom_teacher_id', $user->getKey()))
            ->orderBy('name')
            ->get();

        return view('admin.report-cards.index', compact('reportCards', 'progress', 'semesters', 'academicYears', 'classes', 'filters'));
    }

    public function edit(ReportCard $reportCard, ReportCardExtrasService $service, ReportCardCompletenessService $completeness): View
    {
        // Kepala PKBM needs the same read-only inspection surface as Admin;
        // mutations remain protected by the manageCompleteness ability below.
        Gate::authorize('viewCompleteness', $reportCard);

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
