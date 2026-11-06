<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['institution_id', 'code', 'label', 'minimum_score', 'maximum_score', 'display_order', 'active'])]
class PredicateRange extends Model
{
    protected function casts(): array
    {
        return [
            'minimum_score' => 'integer',
            'maximum_score' => 'integer',
            'display_order' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function contains(int $score): bool
    {
        return $score >= $this->minimum_score && $score <= $this->maximum_score;
    }

    /**
     * @return array{code: string, label: string, minimum_score: int, maximum_score: int}
     */
    public function toSnapshot(): array
    {
        return [
            'code' => $this->code,
            'label' => $this->label,
            'minimum_score' => $this->minimum_score,
            'maximum_score' => $this->maximum_score,
        ];
    }
}
