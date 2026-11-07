<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademicDecision;
use App\Models\AcademicDecisionBatch;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\PeriodPreparation;
use App\Models\ReportCard;

final class PeriodReadinessService
{
    public function __construct(private ReportCardCompletenessService $completeness, private AcademicDecisionService $decisions) {}

    /** @return array<string, mixed> */
    public function evaluate(AcademicYear $source, ?PeriodPreparation $preparation = null): array
    {
        $source = $source->fresh();
        $semester = $source->semesters()->where('status', 'ACTIVE')->first();
        $yearChange = $semester?->code?->value === 'GENAP';
        $enrollments = Enrollment::query()->where('academic_year_id', $source->id)->where('enrollment_status', 'ACTIVE')
            ->with(['student', 'academicClass.level', 'academicClass.program'])->orderBy('class_id')->orderBy('id')->get();
        $decisions = AcademicDecision::query()->where('academic_year_id', $source->id)->get()->keyBy('student_id');
        $batches = AcademicDecisionBatch::query()->where('academic_year_id', $source->id)->get()->keyBy('class_id');
        $reportIds = $enrollments->flatMap(fn ($e) => [$e->id, $e->reporting_enrollment_id])->filter()->unique();
        $reports = ReportCard::query()->whereIn('enrollment_id', $reportIds)->where('semester_id', $semester?->id)
            ->with(['grades', 'attitudeAssessments', 'attendanceSummary', 'extracurriculars', 'homeroomNote'])->get()->keyBy('enrollment_id');
        $preparation?->load(['targetYear', 'placements']);
        $placements = $preparation?->placements->keyBy('student_id') ?? collect();
        $target = $preparation?->targetYear;
        $targetClasses = $target ? AcademicClass::query()->where('academic_year_id', $target->id)->where('status', 'ACTIVE')->with(['level', 'program', 'subjectAssignments', 'homeroomTeacher'])->orderBy('name')->get() : collect();
        $rows = [];
        $groups = [];
        $blockers = [];
        $counts = ['students' => $enrollments->count(), 'PROMOTE' => 0, 'REPEAT' => 0, 'GRADUATE' => 0, 'NOT_GRADUATED' => 0, 'missing_decisions' => 0, 'missing_reports' => 0, 'missing_destinations' => 0, 'unfinalized_classes' => 0];
        $versions = [];
        $unfinalizedClassIds = [];
        if ($source->status->value !== 'ACTIVE' || ! $semester) {
            $blockers[] = 'Tahun dan semester asal harus aktif.';
        }
        if ($yearChange) {
            if (! $target || $target->status->value !== 'DRAFT' || ! $source->end_date || ! $target->start_date || ! $target->end_date || ! $target->start_date->gt($source->end_date) || ! $target->end_date->gt($target->start_date)) {
                $blockers[] = 'Siapkan tahun tujuan Draf dengan tanggal setelah akhir tahun asal.';
            }
            if (! $preparation?->configuration_reviewed) {
                $blockers[] = 'Tinjau kelas, wali kelas, guru, mapel, dan KKM tujuan.';
            }
        }
        if ($target && $target->id !== $source->id) {
            if ($target->semesters()->where('status', '!=', 'DRAFT')->exists() || ReportCard::query()->whereHas('semester', fn ($q) => $q->where('academic_year_id', $target->id))->exists()) {
                $blockers[] = 'Tahun tujuan sudah digunakan; pilih tahun Draf tanpa penilaian.';
            }
        } elseif (! $yearChange && $semester) {
            $next = $source->semesters()->where('code', 'GENAP')->first();
            if ($next && ($next->status->value !== 'DRAFT' || ReportCard::query()->where('semester_id', $next->id)->exists())) {
                $blockers[] = 'Semester Genap sudah digunakan.';
            }
        }
        foreach ($enrollments as $enrollment) {
            $class = $enrollment->academicClass;
            $batch = $batches->get($class->id);
            if ($yearChange && (! $batch || $batch->status !== 'FINALIZED') && ! in_array($class->id, $unfinalizedClassIds, true)) {
                $unfinalizedClassIds[] = $class->id;
            }
            $report = $reports->get($enrollment->reporting_enrollment_id) ?? $reports->get($enrollment->id);
            $summary = $report ? $this->completeness->summary($report) : null;
            $reportReady = $summary && $summary['complete'];
            $decision = $this->decisions->current($enrollment, $decisions->get($enrollment->student_id));
            if ($yearChange && $decision && ! array_key_exists($decision->decision, $this->decisions->options((int) $class->level->number))) {
                $decision = null;
            }
            $placement = $placements->get($enrollment->student_id);
            $destination = $placement ? $targetClasses->firstWhere('id', $placement->class_id) : null;
            $destinationError = null;
            if (! $reportReady) {
                $counts['missing_reports']++;
            }
            if ($yearChange) {
                if (! $decision) {
                    $counts['missing_decisions']++;
                    $destinationError = 'Menunggu keputusan wali kelas.';
                } else {
                    $counts[$decision->decision]++;
                    $finish = $decision->decision === 'GRADUATE' && ((int) $class->level->number === 12 || $placement?->continuation === 'FINISH');
                    if ($decision->decision === 'GRADUATE' && (int) $class->level->number !== 12
                        && (! $placement || $placement->enrollment_id !== $enrollment->id || ! in_array($placement->continuation, ['FINISH', 'CONTINUE'], true))) {
                        $destinationError = 'Pilih kelanjutan setelah lulus.';
                    } elseif (! $finish) {
                        $nextLevel = (int) $class->level->number + (in_array($decision->decision, ['PROMOTE', 'GRADUATE'], true) ? 1 : 0);
                        if (! $placement || $placement->enrollment_id !== $enrollment->id || ! $destination || (int) $destination->level->number !== $nextLevel) {
                            $destinationError = 'Pilih kelas tujuan tingkat '.$nextLevel.'.';
                        } elseif (Enrollment::query()->where('student_id', $enrollment->student_id)->where('academic_year_id', $target?->id)->where('enrollment_status', 'ACTIVE')->exists()) {
                            $destinationError = 'Siswa sudah memiliki penempatan aktif pada tahun tujuan.';
                        }
                    }
                }
                if ($destinationError) {
                    $counts['missing_destinations']++;
                }
            }
            $groups[$class->id] ??= ['class_id' => $class->id, 'name' => $class->name, 'students' => 0, 'missing_reports' => 0, 'missing_decisions' => 0];
            $groups[$class->id]['students']++;
            $groups[$class->id]['missing_reports'] += $reportReady ? 0 : 1;
            $groups[$class->id]['missing_decisions'] += $yearChange && ! $decision ? 1 : 0;
            $level = (int) $class->level->number;
            $expectedLevel = $decision ? $level + (in_array($decision->decision, ['PROMOTE', 'GRADUATE'], true) ? 1 : 0) : null;
            $rows[] = ['enrollment' => $enrollment, 'decision' => $decision, 'placement' => $placement, 'destination' => $destination,
                'destination_error' => $destinationError, 'expected_level' => $expectedLevel,
                'report_ready' => (bool) $reportReady, 'report_missing' => $summary ? collect($summary['missing'])->pluck('label')->all() : ['Rapor belum dibuat']];
            $versions[] = [$enrollment->getAttributes(), $class->getAttributes(), $decision?->getAttributes(), $batch?->getAttributes(), $placement?->getAttributes(), $report?->getAttributes(),
                $report?->grades->toArray(), $report?->attitudeAssessments->toArray(), $report?->attendanceSummary?->toArray(), $report?->extracurriculars->toArray(), $report?->homeroomNote?->toArray()];
        }
        if ($counts['missing_reports']) {
            $blockers[] = $counts['missing_reports'].' rapor belum lengkap.';
        }
        if ($counts['missing_decisions']) {
            $blockers[] = $counts['missing_decisions'].' keputusan wali kelas belum tersedia atau perlu dikonfirmasi ulang.';
        }
        if ($yearChange && $unfinalizedClassIds !== []) {
            $counts['unfinalized_classes'] = count($unfinalizedClassIds);
            $blockers[] = $counts['unfinalized_classes'].' kelas belum menetapkan keputusan akhir wali kelas.';
        }
        if ($counts['missing_destinations']) {
            $blockers[] = $counts['missing_destinations'].' penempatan tujuan belum sesuai.';
        }
        $fingerprint = hash('sha256', json_encode([$source->getAttributes(), $semester?->getAttributes(), $target?->getAttributes(), $targetClasses->toArray(),
            $preparation?->revision, $preparation?->configuration_reviewed, $versions, $blockers], JSON_THROW_ON_ERROR));

        return ['source' => $source, 'semester' => $semester, 'year_change' => $yearChange, 'target' => $target, 'rows' => $rows,
            'classes' => $targetClasses, 'groups' => array_values($groups), 'counts' => $counts, 'blockers' => $blockers,
            'ready' => $blockers === [], 'fingerprint' => $fingerprint];
    }
}
