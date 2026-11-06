<?php

namespace App\Policies;

use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SubjectAssignmentPolicy
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
    public function view(User $user, SubjectAssignment $subjectAssignment): bool
    {
        if (! $this->belongsToInstitution($user, $subjectAssignment)) {
            return false;
        }

        return $user->hasAnyRole(['ADMIN', 'KEPSEK'])
            || ($user->hasRole('GURU') && $subjectAssignment->teacher_id === $user->getKey());
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
    public function update(User $user, SubjectAssignment $subjectAssignment): bool
    {
        return $this->belongsToInstitution($user, $subjectAssignment) && $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SubjectAssignment $subjectAssignment): bool
    {
        return $this->belongsToInstitution($user, $subjectAssignment) && $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SubjectAssignment $subjectAssignment): bool
    {
        return $this->belongsToInstitution($user, $subjectAssignment) && $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SubjectAssignment $subjectAssignment): bool
    {
        return $this->belongsToInstitution($user, $subjectAssignment) && $user->hasRole('ADMIN');
    }

    private function belongsToInstitution(User $user, SubjectAssignment $subjectAssignment): bool
    {
        return $user->isActive()
            && $subjectAssignment->academicClass()
                ->whereHas('program', fn (Builder $query): Builder => $query->where('institution_id', $user->institution_id))
                ->exists();
    }
}
