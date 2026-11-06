<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;

/**
 * Keeps academic-period visibility and mutation scoped to one institution.
 */
class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->institution_id !== null;
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        // Teachers see a year only when they participate in one of its classes;
        // this avoids leaking unrelated periods through list/detail endpoints.
        if (! $this->belongsToInstitution($user, $academicYear)) {
            return false;
        }

        if ($user->hasAnyRole(['ADMIN', 'KEPSEK'])) {
            return true;
        }

        return $academicYear->classes()
            ->where(function ($classes) use ($user): void {
                $classes->where('homeroom_teacher_id', $user->getKey())
                    ->orWhereHas('subjectAssignments', fn ($assignments) => $assignments->where('teacher_id', $user->getKey()));
            })
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasRole('ADMIN') && $user->institution_id !== null;
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $this->belongsToInstitution($user, $academicYear) && $user->hasRole('ADMIN');
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        return $this->belongsToInstitution($user, $academicYear) && $user->hasRole('ADMIN');
    }

    private function belongsToInstitution(User $user, AcademicYear $academicYear): bool
    {
        return $user->isActive()
            && $user->institution_id !== null
            && $user->institution_id === $academicYear->institution_id;
    }
}
