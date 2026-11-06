<?php

namespace App\Policies;

use App\Models\DescriptionSnippet;
use App\Models\User;

/**
 * Separates institution-wide reusable snippets from teacher-owned private text.
 */
class DescriptionSnippetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->institution_id !== null;
    }

    public function view(User $user, DescriptionSnippet $snippet): bool
    {
        if (! $this->belongsToInstitution($user, $snippet)) {
            return false;
        }

        if ($snippet->isShared()) {
            // Shared snippets are reusable within the same institution, including
            // for teachers who did not create them.
            return true;
        }

        return $user->hasRole('GURU')
            && $snippet->isPrivate()
            && $snippet->owner_user_id === $user->getKey();
    }

    public function use(User $user, DescriptionSnippet $snippet): bool
    {
        return $snippet->active && $this->view($user, $snippet);
    }

    public function create(User $user): bool
    {
        return $user->isActive()
            && $user->institution_id !== null
            && $user->hasAnyRole(['ADMIN', 'GURU']);
    }

    public function update(User $user, DescriptionSnippet $snippet): bool
    {
        if (! $this->belongsToInstitution($user, $snippet)) {
            return false;
        }

        if ($user->hasRole('ADMIN')) {
            return $snippet->isShared();
        }

        return $user->hasRole('GURU')
            && $snippet->isPrivate()
            && $snippet->owner_user_id === $user->getKey();
    }

    public function delete(User $user, DescriptionSnippet $snippet): bool
    {
        return $this->update($user, $snippet);
    }

    private function belongsToInstitution(User $user, DescriptionSnippet $snippet): bool
    {
        return $user->isActive()
            && $user->institution_id !== null
            && $user->institution_id === $snippet->institution_id;
    }
}
