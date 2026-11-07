<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ReportCard;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Implements the report-card state machine and its actor/timestamp audit trail.
 */
final class ReportCardWorkflowService
{
    public function __construct(
        private readonly ReportCardIdentityService $identity,
        private readonly ReportCardCompletenessService $completeness,
    ) {}

    public function lock(ReportCard $reportCard, User $actor): ReportCard
    {
        return $this->transition(
            $reportCard,
            $actor,
            'lock',
            ['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'NEEDS_REVISION', 'APPROVED'],
            'LOCKED',
            function (ReportCard $locked, User $user): array {
                $this->completeness->assertComplete($locked);

                return [
                    'locked_at' => now(),
                    'locked_by_user_id' => $user->getKey(),
                    'unlocked_at' => null,
                    'unlocked_by_user_id' => null,
                    'unlock_reason' => null,
                    'identity_snapshot' => $this->identity->snapshot($locked),
                ];
            },
        );
    }

    public function unlock(ReportCard $reportCard, User $actor, ?string $reason = null): ReportCard
    {
        $reason = $reason === null ? '' : trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan membuka kunci wajib diisi.']);
        }

        return $this->transition(
            $reportCard,
            $actor,
            'unlock',
            ['LOCKED'],
            'DRAFT',
            fn (ReportCard $locked, User $user): array => [
                'locked_at' => null,
                'unlocked_at' => now(),
                'unlocked_by_user_id' => $user->getKey(),
                'unlock_reason' => $reason,
            ],
            $reason,
        );
    }

    /** @param array<int, string> $fromStatuses */
    private function transition(
        ReportCard $reportCard,
        User $actor,
        string $ability,
        array $fromStatuses,
        string $toStatus,
        callable $changes,
        ?string $reason = null,
    ): ReportCard {
        // Every transition is serialized and re-authorized against the locked
        // row so a stale screen cannot skip a workflow state.
        Gate::forUser($actor)->authorize($ability, $reportCard);

        return DB::transaction(function () use ($reportCard, $actor, $ability, $fromStatuses, $toStatus, $changes, $reason): ReportCard {
            $locked = ReportCard::query()->lockForUpdate()->findOrFail($reportCard->getKey());
            Gate::forUser($actor)->authorize($ability, $locked);

            if (! in_array($locked->status, $fromStatuses, true)) {
                throw ValidationException::withMessages([
                    'workflow' => sprintf('Rapor berstatus %s tidak dapat diproses dengan aksi %s.', $locked->status, $ability),
                ]);
            }

            $oldStatus = $locked->status;
            $locked->forceFill([
                'status' => $toStatus,
                ...$changes($locked, $actor),
            ])->save();

            $this->audit($locked, $actor, $ability, $oldStatus, $toStatus, $reason);

            return $locked->refresh();
        });
    }

    private function audit(
        ReportCard $reportCard,
        User $actor,
        string $ability,
        string $oldStatus,
        string $newStatus,
        ?string $reason,
    ): void {
        $reportCard->loadMissing('enrollment.academicClass.program');

        AuditLog::query()->create([
            'institution_id' => $reportCard->enrollment->academicClass->program->institution_id,
            'report_card_id' => $reportCard->getKey(),
            'user_id' => $actor->getKey(),
            'action' => 'report_card.'.match ($ability) {
                'lock' => 'locked',
                'unlock' => 'unlocked',
                default => $ability,
            },
            'entity_type' => ReportCard::class,
            'entity_id' => $reportCard->getKey(),
            'old_value_json' => ['status' => $oldStatus],
            'new_value_json' => ['status' => $newStatus],
            'reason' => $reason,
        ]);
    }
}
