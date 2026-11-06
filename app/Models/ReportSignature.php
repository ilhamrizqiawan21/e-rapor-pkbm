<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['report_card_id', 'signer_type', 'user_id', 'signer_name_snapshot', 'signer_title_snapshot', 'signer_employee_number_snapshot', 'signature_image_path', 'signed_at'])]
class ReportSignature extends Model
{
    protected function casts(): array
    {
        return ['signed_at' => 'datetime'];
    }

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
