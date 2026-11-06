<?php

namespace App\Policies;

use App\Models\ClassTransfer;
use App\Models\User;

class ClassTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->institution_id !== null;
    }

    public function view(User $user, ClassTransfer $transfer): bool
    {
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
