<?php

namespace App\Http\Controllers;

use App\Enums\SemesterCode;
use App\Http\Requests\SaveAcademicDecisionsRequest;
use App\Models\AcademicClass;
use App\Models\AcademicDecision;
use App\Services\AcademicDecisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicDecisionController extends Controller
{
    public function index(Request $request, AcademicDecisionService $service): View
    {
        $actor = $request->user();
        abort_unless($actor->isActive() && $actor->hasAnyRole(['ADMIN', 'GURU', 'KEPSEK']), 403);
        $classes = AcademicClass::query()->whereHas('academicYear', fn ($q) => $q->where('institution_id', $actor->institution_id))
            ->when($actor->hasRole('GURU') && ! $actor->hasAnyRole(['ADMIN', 'KEPSEK']), fn ($q) => $q->where('homeroom_teacher_id', $actor->id))
            ->with(['academicYear.semesters', 'level', 'program'])->orderByDesc('academic_year_id')->orderBy('name')->get();
        $class = $request->filled('class_id') ? $classes->firstWhere('id', $request->integer('class_id'))
            : ($classes->first(fn ($c) => $c->academicYear->status->value === 'ACTIVE') ?? $classes->first());
        abort_if($request->filled('class_id') && ! $class, 404);
        $semester = $class?->academicYear->semesters->firstWhere('code', SemesterCode::Genap);
        $activeSemester = $class?->academicYear->semesters->first(fn ($s) => $s->status->value === 'ACTIVE');
        $batch = $class ? $service->batch($class) : null;
        $finalized = $batch?->status === 'FINALIZED';
        $editable = $class && $actor->hasRole('GURU') && ! $actor->hasAnyRole(['ADMIN', 'KEPSEK'])
            && $class->homeroom_teacher_id === $actor->id && $class->academicYear->status->value === 'ACTIVE'
            && $activeSemester?->code?->value === 'GENAP' && ! $finalized;
        $enrollments = $class?->enrollments()->whereIn('enrollment_status', $class->academicYear->status->value === 'CLOSED' ? ['ACTIVE', 'COMPLETED', 'GRADUATED'] : ['ACTIVE'])->with('student')->orderBy('id')->get() ?? collect();
        $decisions = AcademicDecision::query()->where('academic_year_id', $class?->academic_year_id)->get()->keyBy('student_id');
        $rows = $enrollments->map(fn ($e) => ['enrollment' => $e, 'decision' => $service->current($e, $decisions->get($e->student_id)), 'revision' => $decisions->get($e->student_id)?->revision ?? 0]);
        $missing = $rows->whereNull('decision')->count();

        return view('academic-decisions.index', ['classes' => $classes, 'class' => $class, 'semester' => $semester, 'activeSemester' => $activeSemester,
            'editable' => $editable, 'finalized' => $finalized, 'batch' => $batch, 'rows' => $rows, 'decided' => $rows->count() - $missing, 'missing' => $missing,
            'options' => $class ? $service->options((int) $class->level->number) : []]);
    }

    public function update(SaveAcademicDecisionsRequest $request, AcademicClass $academicClass, AcademicDecisionService $service): RedirectResponse
    {
        abort_if($request->user()->hasAnyRole(['ADMIN', 'KEPSEK']), 403);
        $service->save($request->user(), $academicClass, $request->validated('rows'));

        return redirect()->route('academic-decisions.index', ['class_id' => $academicClass->id])->with('status', 'Keputusan kelas berhasil disimpan.');
    }

    public function finalize(Request $request, AcademicClass $academicClass, AcademicDecisionService $service): RedirectResponse
    {
        $service->finalize($request->user(), $academicClass);

        return redirect()->route('academic-decisions.index', ['class_id' => $academicClass->id])->with('status', 'Keputusan akhir kelas sudah ditetapkan.');
    }

    public function reopen(Request $request, AcademicClass $academicClass, AcademicDecisionService $service): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $service->reopen($request->user(), $academicClass, $request->string('reason')->toString());

        return redirect()->route('academic-decisions.index', ['class_id' => $academicClass->id])->with('status', 'Kelas dibuka untuk koreksi.');
    }
}
