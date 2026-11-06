<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['institution_id', 'owner_user_id', 'subject_id', 'title', 'content', 'visibility', 'active'])]
class DescriptionSnippet extends Model
{
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
