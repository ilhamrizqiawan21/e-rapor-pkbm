<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['institution_id', 'code', 'name', 'short_name', 'category', 'display_order', 'active'])]
class Subject extends Model
{
    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }
}
