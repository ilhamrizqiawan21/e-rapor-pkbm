<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

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
        if (! $this->belongsToInstitution($user, $student)) {
            return false;
        }

        if ($user->hasAnyRole(['ADMIN', 'KEPSEK'])) {
            return true;
        }

        return $student->enrollments()
            ->whereHas('academicClass', function (Builder $query) use ($user): void {
                $query->where('homeroom_teacher_id', $user->getKey())
                    ->orWhereHas('subjectAssignments', function (Builder $assignmentQuery) use ($user): void {
                        $assignmentQuery->where('teacher_id', $user->getKey());
                    });
            })
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
