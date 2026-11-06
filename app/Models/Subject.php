<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['institution_id', 'code', 'name', 'short_name', 'category', 'active'])]
class Subject extends Model
{
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }
}
