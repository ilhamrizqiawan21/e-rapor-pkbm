<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdminInInstitution($user);
    }

    public function view(User $user, User $teacher): bool
    {
        return $this->canManageTeacher($user, $teacher);
    }

    public function create(User $user): bool
    {
        return $this->isAdminInInstitution($user);
    }

    public function update(User $user, User $teacher): bool
    {
        return $this->canManageTeacher($user, $teacher);
    }

    public function delete(User $user, User $teacher): bool
    {
        return $this->canManageTeacher($user, $teacher);
    }

    private function canManageTeacher(User $user, User $teacher): bool
    {
        return $this->isAdminInInstitution($user)
            && $user->institution_id === $teacher->institution_id
            && $teacher->hasRole(RoleCode::Guru);
    }

    private function isAdminInInstitution(User $user): bool
    {
        return $user->isActive()
            && $user->institution_id !== null
            && $user->hasRole(RoleCode::Admin);
    }
}
