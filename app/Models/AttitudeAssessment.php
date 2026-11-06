<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['report_card_id', 'attitude_type', 'description'])]
class AttitudeAssessment extends Model
{
    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }
}
