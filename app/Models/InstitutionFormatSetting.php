<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'institution_id',
    'student_name_format',
    'document_number_format',
    'date_format',
    'signature_left_role',
    'signature_left_label',
    'signature_right_role',
    'signature_right_label',
    'show_logo',
    'logo_position',
    'logo_width_mm',
    'margin_top_mm',
    'margin_right_mm',
    'margin_bottom_mm',
    'margin_left_mm',
    'header_text',
    'footer_text',
    'show_kkm',
    'show_predicate',
])]
class InstitutionFormatSetting extends Model
{
    protected function casts(): array
    {
        return [
            'show_logo' => 'boolean',
            'logo_width_mm' => 'integer',
            'margin_top_mm' => 'integer',
            'margin_right_mm' => 'integer',
            'margin_bottom_mm' => 'integer',
            'margin_left_mm' => 'integer',
            'show_kkm' => 'boolean',
            'show_predicate' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
