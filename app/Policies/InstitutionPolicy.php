<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;

/**
 * Prevents cross-institution access and intentionally disallows institution
 * deletion because it is the tenant boundary for all operational records.
 */
class InstitutionPolicy
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
    public function view(User $user, Institution $institution): bool
    {
        return $this->belongsToInstitution($user, $institution);
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
    public function update(User $user, Institution $institution): bool
    {
        return $this->belongsToInstitution($user, $institution) && $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Institution $institution): bool
    {
        // Institutions are retained as the ownership root for audit and report
        // history; removal is an operational procedure, not a CRUD action.
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Institution $institution): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Institution $institution): bool
    {
        return false;
    }

    private function belongsToInstitution(User $user, Institution $institution): bool
    {
        return $user->isActive() && $user->institution_id === $institution->getKey();
    }
}
