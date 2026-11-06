<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->institution_id !== null;
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->isActive()
            && $enrollment->academicClass?->program?->institution_id === $user->institution_id
            && $user->can('view', $enrollment->academicClass);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasRole('ADMIN');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->isActive()
            && $user->hasRole('ADMIN')
            && $enrollment->academicClass?->program?->institution_id === $user->institution_id;
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $this->update($user, $enrollment);
    }
}
