<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Audit history is read-only operational evidence, visible only to Admin and
 * Kepala PKBM within the active institution.
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive()
            && $user->institution_id !== null
            && $user->hasAnyRole(['ADMIN', 'KEPSEK']);
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $this->viewAny($user) && $auditLog->institution_id === $user->institution_id;
    }

    public function update(): bool
    {
        // Audit entries must remain immutable so the history cannot be rewritten
        // by the same interface that displays it.
        return false;
    }

    public function delete(): bool
    {
        return false;
    }
}
