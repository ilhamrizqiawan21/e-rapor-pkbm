<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;

/** Validates the upload envelope; workbook semantics are checked in the service. */
class PreviewGradeImportRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ];
    }
}
