<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['report_card_id', 'signer_type', 'user_id', 'signer_name_snapshot', 'signer_title_snapshot', 'signature_image_path', 'signed_at'])]
class ReportSignature extends Model
{
    protected function casts(): array
    {
        return ['signed_at' => 'datetime'];
    }
}
