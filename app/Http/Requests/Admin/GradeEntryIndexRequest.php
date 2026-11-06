<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;

/** Validates grade-grid filters while leaving relation scoping to the service. */
class GradeEntryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ReportCard::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer'],
            'semester_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'subject_assignment_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
