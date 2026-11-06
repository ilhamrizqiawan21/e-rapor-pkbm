<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['institution_id', 'report_card_id', 'user_id', 'action', 'entity_type', 'entity_id', 'old_value_json', 'new_value_json', 'reason'])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return ['old_value_json' => 'array', 'new_value_json' => 'array'];
    }
}
