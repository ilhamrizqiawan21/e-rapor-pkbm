<?php

namespace App\Models;

use App\Enums\SignatorySource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'institution_id',
    'headmaster_source',
    'headmaster_user_id',
    'headmaster_name',
    'headmaster_title',
    'headmaster_employee_number',
])]
class InstitutionSignatorySetting extends Model
{
    protected function casts(): array
    {
        return ['headmaster_source' => SignatorySource::class];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function headmasterUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'headmaster_user_id');
    }
}
