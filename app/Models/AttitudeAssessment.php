<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['report_card_id', 'attitude_type', 'description'])]
class AttitudeAssessment extends Model {}
