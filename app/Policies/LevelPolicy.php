<?php

namespace App\Policies;

use App\Models\Level;
use App\Models\User;

/**
 * Authorizes level configuration through its owning program and institution.
 */
class LevelPolicy
{
    public function view(User $user, Level $level): bool
    {
        return $user->isActive()
            && $level->program()->where('institution_id', $user->institution_id)->exists();
    }

    public function create(User $user, Level $level): bool
    {
        return $this->belongsToInstitution($user, $level) && $user->hasRole('ADMIN');
    }

    public function update(User $user, Level $level): bool
    {
        return $this->belongsToInstitution($user, $level) && $user->hasRole('ADMIN');
    }

    public function delete(User $user, Level $level): bool
    {
        // A level with classes is referenced by academic history and cannot be
        // deleted without orphaning that history.
        return $this->belongsToInstitution($user, $level)
            && $user->hasRole('ADMIN')
            && ! $level->academicClasses()->exists();
    }

    private function belongsToInstitution(User $user, Level $level): bool
    {
        return $user->isActive()
            && $level->program()->where('institution_id', $user->institution_id)->exists();
    }
}
