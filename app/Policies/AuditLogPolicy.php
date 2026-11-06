<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

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
        return false;
    }

    public function delete(): bool
    {
        return false;
    }
}
