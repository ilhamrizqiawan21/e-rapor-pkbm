<?php

namespace App\Http\Requests\Admin;

use App\Models\SubjectAssignment;
use Illuminate\Foundation\Http\FormRequest;

/** Validates the bounded matrix used to create class-subject assignments. */
class BulkStoreSubjectAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SubjectAssignment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['required', 'integer', 'distinct'],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['required', 'integer', 'distinct'],
            'teacher_id' => ['required', 'integer'],
            'minimum_score' => ['required', 'integer', 'between:0,100'],
        ];
    }
}
