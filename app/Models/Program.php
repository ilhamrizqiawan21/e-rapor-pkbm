<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['institution_id', 'code', 'name'])]
class Program extends Model
{
    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }
}
