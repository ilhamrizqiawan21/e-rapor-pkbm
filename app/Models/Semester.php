<?php

namespace App\Models;

use App\Enums\SemesterCode;
use App\Enums\SemesterStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['academic_year_id', 'code', 'name', 'sequence', 'status'])]
class Semester extends Model
{
    protected function casts(): array
    {
        return [
            'code' => SemesterCode::class,
            'status' => SemesterStatus::class,
            'sequence' => 'integer',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
