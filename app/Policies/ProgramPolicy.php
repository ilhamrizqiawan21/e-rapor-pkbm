<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->institution_id !== null;
    }

    public function view(User $user, Program $program): bool
    {
        return $this->belongsToInstitution($user, $program);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasRole('ADMIN') && $user->institution_id !== null;
    }

    public function update(User $user, Program $program): bool
    {
        return $this->belongsToInstitution($user, $program) && $user->hasRole('ADMIN');
    }

    public function delete(User $user, Program $program): bool
    {
        return $this->belongsToInstitution($user, $program)
            && $user->hasRole('ADMIN')
            && ! $program->levels()->exists()
            && ! $program->academicClasses()->exists();
    }

    private function belongsToInstitution(User $user, Program $program): bool
    {
        return $user->isActive() && $user->institution_id === $program->institution_id;
    }
}
