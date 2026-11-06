<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['report_card_id', 'activity_name', 'description', 'display_order'])]
class ReportExtracurricular extends Model {}
