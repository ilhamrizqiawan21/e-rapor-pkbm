<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\User;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(
        ?User $actor,
        string $action,
        string $entityType,
        int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?int $institutionId = null,
    ): void {
        $institutionId ??= $actor?->institution_id;

        if ($institutionId === null) {
            return;
        }

        AuditLog::query()->create([
            'institution_id' => $institutionId,
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_value_json' => $oldValues,
            'new_value_json' => $newValues,
            'reason' => $reason,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function recordForInstitution(
        Institution $institution,
        string $action,
        string $entityType,
        int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
    ): void {
        $this->record(null, $action, $entityType, $entityId, $oldValues, $newValues, $reason, $institution->getKey());
    }
}
