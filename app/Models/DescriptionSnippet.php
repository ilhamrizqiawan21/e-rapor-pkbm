<?php

namespace App\Models;

use App\Enums\DescriptionSnippetVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['institution_id', 'owner_user_id', 'subject_id', 'title', 'material', 'content', 'visibility', 'active'])]
class DescriptionSnippet extends Model
{
    protected function casts(): array
    {
        return [
            'visibility' => DescriptionSnippetVisibility::class,
            'active' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function reportGrades(): HasMany
    {
        return $this->hasMany(ReportGrade::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(DescriptionSnippetTemplate::class);
    }

    public function scopeForInstitution(Builder $query, Institution|int $institution): Builder
    {
        $institutionId = $institution instanceof Institution ? $institution->getKey() : $institution;

        return $query->where('institution_id', $institutionId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeShared(Builder $query): Builder
    {
        return $query->where('visibility', DescriptionSnippetVisibility::Shared->value);
    }

    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('visibility', DescriptionSnippetVisibility::Private->value);
    }

    public function scopeOwnedBy(Builder $query, User|int $owner): Builder
    {
        $ownerId = $owner instanceof User ? $owner->getKey() : $owner;

        return $query->where('owner_user_id', $ownerId);
    }

    /**
     * Return snippets a user is allowed to view, including inactive snippets
     * for management screens. Use the active scope when inserting text.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query
            ->forInstitution((int) $user->institution_id)
            ->where(function (Builder $visibilityQuery) use ($user): void {
                $visibilityQuery->shared();

                if (! $user->hasRole('ADMIN') && $user->hasRole('GURU')) {
                    $visibilityQuery->orWhere(function (Builder $privateQuery) use ($user): void {
                        $privateQuery
                            ->private()
                            ->ownedBy($user);
                    });
                }
            });
    }

    public function isShared(): bool
    {
        return $this->visibility === DescriptionSnippetVisibility::Shared;
    }

    public function isPrivate(): bool
    {
        return $this->visibility === DescriptionSnippetVisibility::Private;
    }
}
