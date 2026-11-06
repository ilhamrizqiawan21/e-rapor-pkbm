<?php

namespace App\Policies;

use App\Models\AcademicClass;
use App\Models\User;

/**
 * Authorizes class access within the user's institution and teaching scope.
 */
class AcademicClassPolicy
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
    public function view(User $user, AcademicClass $academicClass): bool
    {
        // Admin and headmaster may inspect all local classes; teachers are
        // limited to homeroom or subject-assignment relationships.
        if (! $this->belongsToInstitution($user, $academicClass)) {
            return false;
        }

        if ($user->hasAnyRole(['ADMIN', 'KEPSEK'])) {
            return true;
        }

        return $academicClass->homeroom_teacher_id === $user->getKey()
            || $academicClass->subjectAssignments()->where('teacher_id', $user->getKey())->exists();
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
    public function update(User $user, AcademicClass $academicClass): bool
    {
        return $this->belongsToInstitution($user, $academicClass) && $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AcademicClass $academicClass): bool
    {
        return $this->belongsToInstitution($user, $academicClass) && $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AcademicClass $academicClass): bool
    {
        return $this->belongsToInstitution($user, $academicClass) && $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AcademicClass $academicClass): bool
    {
        return $this->belongsToInstitution($user, $academicClass) && $user->hasRole('ADMIN');
    }

    private function belongsToInstitution(User $user, AcademicClass $academicClass): bool
    {
        // Checking both relations prevents an arbitrary class/year id from
        // crossing institution boundaries through route model binding.
        return $user->isActive()
            && $academicClass->program()->where('institution_id', $user->institution_id)->exists()
            && $academicClass->academicYear()->where('institution_id', $user->institution_id)->exists();
    }
}
