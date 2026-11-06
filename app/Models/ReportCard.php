<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['enrollment_id', 'semester_id', 'document_number', 'status', 'issued_date', 'published_at', 'locked_at', 'unlocked_at'])]
class ReportCard extends Model
{
    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'published_at' => 'datetime',
            'locked_at' => 'datetime',
            'unlocked_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(ReportGrade::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ReportSignature::class);
    }
}
