<?php

namespace App\Services;

use App\Models\ReportCard;
use App\Models\ReportGrade;
use App\Models\SubjectAssignment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class ReportGradeSnapshotService
{
    public function __construct(
        private readonly PredicateRangeService $predicateRanges,
        private readonly KkmAssignmentService $kkm,
    ) {}

    public function createSnapshot(
        ReportCard $reportCard,
        SubjectAssignment $assignment,
        ?int $score = null,
        ?string $description = null,
    ): ReportGrade {
        $this->ensureEditable($reportCard);
        $this->kkm->assertScore($score);
        $this->assertAssignmentBelongsToReport($reportCard, $assignment);

        $assignment->loadMissing(['subject', 'academicClass.program']);
        $institution = $assignment->academicClass->program->institution;
        $this->predicateRanges->ensureDefaults($institution);
        $rules = $this->predicateRanges->snapshotRules($institution);
        $predicate = $score === null ? null : $this->predicateRanges->predicateFromSnapshot($rules, $score);

        return DB::transaction(function () use ($reportCard, $assignment, $score, $description, $rules, $predicate): ReportGrade {
            return $reportCard->grades()->updateOrCreate(
                ['subject_assignment_id' => $assignment->getKey()],
                [
                    'description_snippet_id' => null,
                    'subject_name_snapshot' => $assignment->subject->name,
                    'subject_short_name_snapshot' => $assignment->subject->short_name,
                    'minimum_score_snapshot' => $this->kkm->assertValid($assignment->minimum_score),
                    'predicate_snapshot' => $predicate['code'] ?? null,
                    'predicate_ranges_snapshot' => $rules,
                    'display_order_snapshot' => $assignment->display_order,
                    'score' => $score,
                    'predicate' => $predicate['code'] ?? null,
                    'description' => $description,
                    'status' => 'DRAFT',
                ],
            );
        });
    }

    public function updateScore(ReportGrade $grade, ?int $score): ReportGrade
    {
        $this->ensureEditable($grade->reportCard);
        $this->kkm->assertScore($score);

        $rules = $grade->predicate_ranges_snapshot ?? [];
        $predicate = $score === null ? null : $this->predicateRanges->predicateFromSnapshot($rules, $score);

        $grade->update([
            'score' => $score,
            'predicate' => $predicate['code'] ?? null,
        ]);

        return $grade->refresh();
    }

    private function ensureEditable(ReportCard $reportCard): void
    {
        if ($reportCard->status === 'LOCKED' || $reportCard->locked_at !== null) {
            throw new LogicException('Rapor terkunci dan snapshot nilai tidak dapat diubah.');
        }
    }

    private function assertAssignmentBelongsToReport(ReportCard $reportCard, SubjectAssignment $assignment): void
    {
        $reportCard->loadMissing('enrollment');
        $assignment->loadMissing('academicClass');

        if ($reportCard->enrollment?->class_id !== $assignment->class_id) {
            throw new InvalidArgumentException('Penugasan mata pelajaran tidak berasal dari kelas rapor.');
        }
    }
}
