<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->institution_id !== null;
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $this->belongsToInstitution($user, $academicYear);
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
