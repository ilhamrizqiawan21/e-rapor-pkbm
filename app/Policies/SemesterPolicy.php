<?php

namespace App\Policies;

use App\Enums\AcademicYearStatus;
use App\Enums\SemesterStatus;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\User;

/**
 * Prevents edits to semesters after either the semester or parent year closes.
 */
class SemesterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->institution_id !== null;
    }

    public function view(User $user, Semester $semester): bool
    {
        return $this->belongsToInstitution($user, $semester);
    }

    public function create(User $user, AcademicYear $academicYear): bool
    {
        return $user->isActive()
            && $user->hasRole('ADMIN')
            && $user->institution_id === $academicYear->institution_id
            && $academicYear->status !== AcademicYearStatus::Closed;
    }

    public function update(User $user, Semester $semester): bool
    {
        // Parent-period closure is inherited by its semesters to protect the
        // historical meaning of published grades.
        return $this->belongsToInstitution($user, $semester)
            && $user->hasRole('ADMIN')
            && $semester->status !== SemesterStatus::Closed
            && $semester->academicYear->status !== AcademicYearStatus::Closed;
    }

    public function delete(User $user, Semester $semester): bool
    {
        return $this->belongsToInstitution($user, $semester)
            && $user->hasRole('ADMIN')
            && $semester->status !== SemesterStatus::Closed
            && $semester->academicYear->status !== AcademicYearStatus::Closed;
    }

    private function belongsToInstitution(User $user, Semester $semester): bool
    {
        return $user->isActive()
            && $user->institution_id !== null
            && $semester->academicYear->institution_id === $user->institution_id;
    }
}
