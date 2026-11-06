<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;

/** Validates a grade batch while preserving per-row score bounds. */
class StoreReportGradesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ReportCard::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer'],
            'semester_id' => ['required', 'integer'],
            'class_id' => ['required', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'subject_assignment_id' => ['required', 'integer'],
            'rows' => ['required', 'array', 'max:100'],
            'rows.*.enrollment_id' => ['required', 'integer'],
            'rows.*.score' => ['nullable'],
            'rows.*.description' => ['nullable', 'string', 'max:10000'],
            'rows.*.description_snippet_id' => ['nullable', 'integer'],
            'rows.*.expected_updated_at' => ['nullable', 'date'],
        ];
    }
}
