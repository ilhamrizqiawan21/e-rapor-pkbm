<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;

/** Validates the shared learning-material context used by automatic grade saves. */
class StoreGradeEntryContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ReportCard::class)
            && $this->user()?->hasAnyRole(['ADMIN', 'GURU']);
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'min:1'],
            'semester_id' => ['required', 'integer', 'min:1'],
            'class_id' => ['required', 'integer', 'min:1'],
            'subject_id' => ['nullable', 'integer', 'min:1'],
            'subject_assignment_id' => ['required', 'integer', 'min:1'],
            'material' => ['nullable', 'string', 'max:10000'],
            'description_snippet_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
