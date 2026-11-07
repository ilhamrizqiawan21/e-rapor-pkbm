<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['student_id', 'academic_year_id', 'semester_id', 'enrollment_id', 'class_id', 'decided_by', 'decision', 'label_snapshot', 'revision', 'decided_at'])]
class AcademicDecision extends Model
{
    protected function casts(): array
    {
        return ['decided_at' => 'datetime', 'revision' => 'integer'];
    }
}
