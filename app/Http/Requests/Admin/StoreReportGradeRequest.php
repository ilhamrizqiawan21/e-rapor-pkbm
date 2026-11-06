<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;

class StoreReportGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ReportCard::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $enrollmentId = (string) $this->input('enrollment_id');
        $row = $enrollmentId !== '' ? $this->input('rows.'.$enrollmentId, []) : [];

        if (is_array($row) && $row !== []) {
            $this->merge([
                'score' => $row['score'] ?? null,
                'description' => $row['description'] ?? null,
                'description_snippet_id' => $row['description_snippet_id'] ?? null,
                'expected_updated_at' => $row['expected_updated_at'] ?? null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer'],
            'semester_id' => ['required', 'integer'],
            'class_id' => ['required', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'subject_assignment_id' => ['required', 'integer'],
            'enrollment_id' => ['required', 'integer'],
            'score' => ['nullable', 'integer', 'between:0,100'],
            'description' => ['nullable', 'string', 'max:10000'],
            'description_snippet_id' => ['nullable', 'integer'],
            'expected_updated_at' => ['nullable', 'date'],
            'rows' => ['nullable', 'array'],
        ];
    }
}
