<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['institution_id', 'student_number', 'national_student_number', 'full_name', 'gender', 'birth_place', 'birth_date', 'address', 'guardian_name', 'status'])]
class Student extends Model
{
    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Limit students to the institution and classes visible to a user.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $query->where('institution_id', $user->institution_id);

        if ($user->hasAnyRole(['ADMIN', 'KEPSEK'])) {
            return $query;
        }

        return $query->whereHas('enrollments.academicClass', function (Builder $classQuery) use ($user): void {
            $classQuery
                ->where('homeroom_teacher_id', $user->getKey())
                ->orWhereHas('subjectAssignments', function (Builder $assignmentQuery) use ($user): void {
                    $assignmentQuery->where('teacher_id', $user->getKey());
                });
        });
    }
}
