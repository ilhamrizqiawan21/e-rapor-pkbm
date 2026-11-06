<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_id', 'class_id', 'academic_year_id', 'start_date', 'end_date', 'enrollment_status', 'reporting_enrollment_id', 'notes'])]
class Enrollment extends Model
{
    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function reportingEnrollment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_enrollment_id');
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(ClassTransfer::class, 'from_enrollment_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(ClassTransfer::class, 'to_enrollment_id');
    }
}
