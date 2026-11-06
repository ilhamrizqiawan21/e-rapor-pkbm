<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['institution_id', 'student_number', 'national_student_number', 'full_name', 'gender', 'birth_place', 'birth_date', 'address', 'guardian_name', 'status'])]
class Student extends Model
{
    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
