<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ReportCard;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ReportCardWorkflowService
{
    public function __construct(
        private readonly ReportCardIdentityService $identity,
        private readonly ReportCardCompletenessService $completeness,
    ) {}

    public function submit(ReportCard $reportCard, User $actor): ReportCard
    {
        return $this->transition(
            $reportCard,
            $actor,
            'submit',
            ['DRAFT', 'NEEDS_REVISION'],
            'SUBMITTED',
            fn (ReportCard $locked, User $user): array => [
                'submitted_at' => now(),
                'submitted_by_user_id' => $user->getKey(),
                'reviewed_at' => null,
                'reviewed_by_user_id' => null,
                'rejected_at' => null,
                'rejected_by_user_id' => null,
                'rejection_reason' => null,
                'approved_at' => null,
                'approved_by_user_id' => null,
            ],
        );
    }

    public function review(ReportCard $reportCard, User $actor): ReportCard
    {
        return $this->transition(
            $reportCard,
            $actor,
            'review',
            ['SUBMITTED'],
            'IN_REVIEW',
            fn (ReportCard $locked, User $user): array => [
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $user->getKey(),
            ],
        );
    }

    public function reject(ReportCard $reportCard, User $actor, string $reason): ReportCard
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan penolakan wajib diisi.']);
        }

        return $this->transition(
            $reportCard,
            $actor,
            'reject',
            ['IN_REVIEW'],
            'NEEDS_REVISION',
            fn (ReportCard $locked, User $user): array => [
                'rejected_at' => now(),
                'rejected_by_user_id' => $user->getKey(),
                'rejection_reason' => $reason,
                'approved_at' => null,
                'approved_by_user_id' => null,
            ],
            $reason,
        );
    }

    public function approve(ReportCard $reportCard, User $actor): ReportCard
    {
        return $this->transition(
            $reportCard,
            $actor,
            'approve',
            ['IN_REVIEW'],
            'APPROVED',
            function (ReportCard $locked, User $user): array {
                $this->completeness->assertComplete($locked);

                return [
                    'approved_at' => now(),
                    'approved_by_user_id' => $user->getKey(),
                ];
            },
        );
    }

    public function lock(ReportCard $reportCard, User $actor): ReportCard
    {
        return $this->transition(
            $reportCard,
            $actor,
            'lock',
            ['APPROVED'],
            'LOCKED',
            fn (ReportCard $locked, User $user): array => [
                'locked_at' => now(),
                'locked_by_user_id' => $user->getKey(),
                'unlocked_at' => null,
                'unlocked_by_user_id' => null,
                'unlock_reason' => null,
                'identity_snapshot' => $this->identity->snapshot($locked),
            ],
        );
    }

    public function unlock(ReportCard $reportCard, User $actor, ?string $reason = null): ReportCard
    {
        $reason = $reason === null ? null : trim($reason);

        return $this->transition(
            $reportCard,
            $actor,
            'unlock',
            ['LOCKED'],
            'APPROVED',
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
                'submit' => 'submitted',
                'review' => 'reviewed',
                'reject' => 'rejected',
                'approve' => 'approved',
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
