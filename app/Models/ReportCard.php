<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'enrollment_id',
    'semester_id',
    'document_number',
    'status',
    'issued_date',
    'published_at',
    'submitted_at',
    'submitted_by_user_id',
    'reviewed_at',
    'reviewed_by_user_id',
    'rejected_at',
    'rejected_by_user_id',
    'rejection_reason',
    'approved_at',
    'approved_by_user_id',
    'locked_at',
    'locked_by_user_id',
    'unlocked_at',
    'unlocked_by_user_id',
    'unlock_reason',
])]
class ReportCard extends Model
{
    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'published_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
            'unlocked_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(ReportGrade::class);
    }

    public function attitudeAssessments(): HasMany
    {
        return $this->hasMany(AttitudeAssessment::class);
    }

    public function attendanceSummary(): HasOne
    {
        return $this->hasOne(AttendanceSummary::class);
    }

    public function extracurriculars(): HasMany
    {
        return $this->hasMany(ReportExtracurricular::class)->orderBy('display_order')->orderBy('id');
    }

    public function homeroomNote(): HasOne
    {
        return $this->hasOne(HomeroomNote::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ReportSignature::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    public function unlockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
