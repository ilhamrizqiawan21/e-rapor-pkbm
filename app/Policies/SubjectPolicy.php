<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

/**
 * Authorizes customizable subject configuration within one institution.
 */
class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->institution_id !== null;
    }

    public function view(User $user, Subject $subject): bool
    {
        return $this->belongsToInstitution($user, $subject);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasRole('ADMIN') && $user->institution_id !== null;
    }

    public function update(User $user, Subject $subject): bool
    {
        return $this->belongsToInstitution($user, $subject) && $user->hasRole('ADMIN');
    }

    public function delete(User $user, Subject $subject): bool
    {
        // Assigned subjects are historical/reporting dependencies and cannot be
        // removed while any class still references them.
        return $this->belongsToInstitution($user, $subject)
            && $user->hasRole('ADMIN')
            && ! $subject->assignments()->exists();
    }

    private function belongsToInstitution(User $user, Subject $subject): bool
    {
        return $user->isActive() && $user->institution_id === $subject->institution_id;
    }
}
