<?php

namespace App\Policies;

use App\Models\ClassTransfer;
use App\Models\User;

/**
 * Controls visibility of class-transfer records without granting teachers the
 * ability to approve or create transfers.
 */
class ClassTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->institution_id !== null;
    }

    public function view(User $user, ClassTransfer $transfer): bool
    {
        // Relations are loaded before authorization because a transfer itself
        // does not carry every institution/class boundary needed for the check.
        $transfer->loadMissing('student', 'fromEnrollment.academicClass', 'toEnrollment.academicClass');

        return $user->isActive()
            && $transfer->student?->institution_id === $user->institution_id
            && ($user->hasAnyRole(['ADMIN', 'KEPSEK'])
                || $user->can('view', $transfer->fromEnrollment?->academicClass)
                || $user->can('view', $transfer->toEnrollment?->academicClass));
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasRole('ADMIN');
    }
}
