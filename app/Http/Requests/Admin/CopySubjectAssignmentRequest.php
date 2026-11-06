<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicClass;
use App\Models\SubjectAssignment;
use Illuminate\Foundation\Http\FormRequest;

/** Requires access to the target and source class before copying configuration. */
class CopySubjectAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academicClass = $this->route('academic_class');

        return $academicClass instanceof AcademicClass
            && ($this->user()?->can('create', SubjectAssignment::class) ?? false)
            && ($this->user()?->can('view', $academicClass) ?? false);
    }

    public function rules(): array
    {
        return [
            'source_class_id' => ['required', 'integer', 'different:academic_class'],
        ];
    }
}
