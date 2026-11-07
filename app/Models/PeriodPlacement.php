<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['period_preparation_id', 'student_id', 'enrollment_id', 'class_id', 'continuation'])]
class PeriodPlacement extends Model {}
