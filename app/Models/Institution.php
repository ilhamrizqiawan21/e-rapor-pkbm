<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'address',
    'phone',
    'email',
    'logo_path',
    'npsn',
    'permit_number',
    'postal_code',
])]
class Institution extends Model
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function signatorySetting(): HasOne
    {
        return $this->hasOne(InstitutionSignatorySetting::class);
    }
}
