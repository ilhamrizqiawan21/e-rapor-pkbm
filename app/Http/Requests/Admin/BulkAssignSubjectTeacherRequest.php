<?php

namespace App\Http\Requests\Admin;

use App\Models\SubjectAssignment;
use Illuminate\Foundation\Http\FormRequest;

/** Validates an Admin-only teacher assignment batch without duplicate ids. */
class BulkAssignSubjectTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SubjectAssignment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'assignment_ids' => ['required', 'array', 'min:1'],
            'assignment_ids.*' => ['required', 'integer', 'distinct'],
            'teacher_id' => ['required', 'integer'],
        ];
    }
}
