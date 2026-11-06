<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

/**
 * Restricts student records to the tenant-aware Student::visibleTo scope.
 */
class StudentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->institution_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Student $student): bool
    {
        // Use the model scope rather than trusting the bound record's foreign
        // keys; this keeps detail access aligned with list/search privacy rules.
        return $student->newQuery()
            ->whereKey($student->getKey())
            ->visibleTo($user)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Student $student): bool
    {
        return $this->belongsToInstitution($user, $student) && $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Student $student): bool
    {
        return $this->belongsToInstitution($user, $student) && $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Student $student): bool
    {
        return $this->belongsToInstitution($user, $student) && $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        return false;
    }

    private function belongsToInstitution(User $user, Student $student): bool
    {
        return $user->isActive() && $user->institution_id === $student->institution_id;
    }
}
