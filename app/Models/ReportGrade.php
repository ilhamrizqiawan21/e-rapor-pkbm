<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['report_card_id', 'subject_assignment_id', 'description_snippet_id', 'subject_name_snapshot', 'subject_short_name_snapshot', 'minimum_score_snapshot', 'display_order_snapshot', 'score', 'predicate', 'description', 'status'])]
class ReportGrade extends Model
{
    protected function casts(): array
    {
        return ['score' => 'integer'];
    }
}
