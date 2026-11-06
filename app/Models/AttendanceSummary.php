<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['report_card_id', 'sick_days', 'leave_days', 'unexcused_days'])]
class AttendanceSummary extends Model
{
    protected function casts(): array
    {
        return [
            'sick_days' => 'integer',
            'leave_days' => 'integer',
            'unexcused_days' => 'integer',
        ];
    }

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }
}
