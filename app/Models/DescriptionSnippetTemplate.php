<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['description_snippet_id', 'predicate_code', 'predicate_label', 'template', 'active'])]
class DescriptionSnippetTemplate extends Model
{
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function snippet(): BelongsTo
    {
        return $this->belongsTo(DescriptionSnippet::class, 'description_snippet_id');
    }
}
