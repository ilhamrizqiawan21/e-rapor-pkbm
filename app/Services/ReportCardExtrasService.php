<?php

namespace App\Services;

use App\Models\HomeroomNote;
use App\Models\ReportCard;
use App\Models\ReportExtracurricular;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ReportCardExtrasService
{
    public const HOMEROOM_NOTE_MAX_LENGTH = 500;

    /**
     * @return array{reportCard: ReportCard, extracurriculars: Collection<int, ReportExtracurricular>, homeroomNote: ?HomeroomNote, noteMaxLength: int}
     */
    public function formData(ReportCard $reportCard): array
    {
        $reportCard->load([
            'enrollment.student',
            'enrollment.academicClass.homeroomTeacher',
            'enrollment.academicClass.program',
            'semester.academicYear',
            'extracurriculars',
            'homeroomNote',
        ]);

        return [
            'reportCard' => $reportCard,
            'extracurriculars' => $reportCard->extracurriculars,
            'homeroomNote' => $reportCard->homeroomNote,
            'noteMaxLength' => self::HOMEROOM_NOTE_MAX_LENGTH,
        ];
    }

    /** @param array<int, array{activity_name: string, description?: string|null}> $rows */
    public function replaceExtracurriculars(ReportCard $reportCard, User $actor, array $rows): Collection
    {
        return DB::transaction(function () use ($reportCard, $actor, $rows): Collection {
            $locked = $this->lockEditableReportCard($reportCard, $actor);
            $locked->extracurriculars()->delete();

            $saved = collect();
            foreach ($this->normalizedRows($rows) as $index => $row) {
                $saved->push($locked->extracurriculars()->create([
                    'activity_name' => $row['activity_name'],
                    'description' => $row['description'],
                    'display_order' => $index + 1,
                ]));
            }

            return $saved;
        });
    }

    public function saveHomeroomNote(ReportCard $reportCard, User $actor, ?string $note): ?HomeroomNote
    {
        return DB::transaction(function () use ($reportCard, $actor, $note): ?HomeroomNote {
            $locked = $this->lockEditableReportCard($reportCard, $actor);
            $value = trim((string) ($note ?? ''));

            if ($value === '') {
                $locked->homeroomNote()->delete();

                return null;
            }

            return $locked->homeroomNote()->updateOrCreate([], ['note' => $value]);
        });
    }

    /** @param array<int, array{activity_name?: mixed, description?: mixed}> $rows */
    private function normalizedRows(array $rows): array
    {
        return collect($rows)
            ->map(static fn (array $row): array => [
                'activity_name' => trim((string) ($row['activity_name'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
            ])
            ->filter(static fn (array $row): bool => $row['activity_name'] !== '')
            ->values()
            ->all();
    }

    private function lockEditableReportCard(ReportCard $reportCard, User $actor): ReportCard
    {
        $locked = ReportCard::query()
            ->with(['enrollment.academicClass.program'])
            ->lockForUpdate()
            ->findOrFail($reportCard->getKey());

        Gate::forUser($actor)->authorize('manageCompleteness', $locked);

        if ($locked->status === 'LOCKED') {
            throw ValidationException::withMessages([
                'report_card' => 'Rapor yang sudah dikunci tidak dapat diubah.',
            ]);
        }

        return $locked;
    }
}
