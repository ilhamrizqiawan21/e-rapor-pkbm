<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'institution_id',
    'student_number',
    'national_student_number',
    'nik',
    'family_card_number',
    'full_name',
    'nickname',
    'gender',
    'birth_place',
    'birth_date',
    'religion',
    'citizenship',
    'birth_order',
    'siblings_count',
    'address',
    'postal_code',
    'phone_number',
    'living_with',
    'transportation_mode',
    'father_name',
    'father_nik',
    'father_birth_place',
    'father_birth_date',
    'father_education',
    'father_occupation',
    'father_income',
    'father_phone',
    'mother_name',
    'mother_nik',
    'mother_birth_place',
    'mother_birth_date',
    'mother_education',
    'mother_occupation',
    'mother_income',
    'mother_phone',
    'guardian_name',
    'guardian_relation',
    'guardian_occupation',
    'guardian_phone',
    'previous_school_name',
    'previous_diploma_number',
    'entry_date',
    'status',
    'graduation_certificate_number',
])]
class Student extends Model
{
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'father_birth_date' => 'date',
            'mother_birth_date' => 'date',
            'entry_date' => 'date',
            'birth_order' => 'integer',
            'siblings_count' => 'integer',
        ];
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
