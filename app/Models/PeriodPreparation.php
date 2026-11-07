<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['source_year_id', 'source_semester_id', 'target_year_id', 'target_semester_id', 'prepared_by', 'kind', 'status', 'revision', 'configuration_reviewed', 'review_hash', 'completed_at'])]
class PeriodPreparation extends Model
{
    protected function casts(): array
    {
        return ['configuration_reviewed' => 'boolean', 'revision' => 'integer', 'completed_at' => 'datetime'];
    }

    public function targetYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'target_year_id');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(PeriodPlacement::class);
    }
}
