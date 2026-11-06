<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

/**
 * Applies the class access boundary to student placement records.
 */
class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->institution_id !== null;
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        // Delegating class scope to AcademicClassPolicy keeps enrollment access
        // consistent with the class and teaching relationships used elsewhere.
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
