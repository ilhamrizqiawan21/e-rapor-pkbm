<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['student_id', 'from_enrollment_id', 'to_enrollment_id', 'effective_date', 'reason', 'notes', 'approved_by_admin_id'])]
class ClassTransfer extends Model
{
    protected function casts(): array
    {
        return ['effective_date' => 'date'];
    }
}
