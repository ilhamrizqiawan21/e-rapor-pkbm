<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['report_card_id', 'sick_days', 'leave_days', 'unexcused_days'])]
class AttendanceSummary extends Model {}
