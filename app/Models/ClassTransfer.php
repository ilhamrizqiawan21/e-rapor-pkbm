<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'from_enrollment_id', 'to_enrollment_id', 'effective_date', 'reason', 'notes', 'approved_by_admin_id'])]
class ClassTransfer extends Model
{
    protected function casts(): array
    {
        return ['effective_date' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromEnrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'from_enrollment_id');
    }

    public function toEnrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'to_enrollment_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_admin_id');
    }
}
