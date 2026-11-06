<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicClass;
use App\Models\SubjectAssignment;
use Illuminate\Foundation\Http\FormRequest;

/** Normalizes either assignment ids or display orders into one safe ordering. */
class ReorderSubjectAssignmentsRequest extends FormRequest
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
            'assignment_ids' => ['nullable', 'array', 'min:1'],
            'assignment_ids.*' => ['required', 'integer', 'distinct'],
            'display_orders' => ['nullable', 'array', 'min:1'],
            'display_orders.*' => ['required', 'integer', 'min:1', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('assignment_ids') && $this->filled('display_orders')) {
            $orders = collect($this->input('display_orders', []))
                ->mapWithKeys(fn ($order, $assignmentId): array => [(int) $assignmentId => (int) $order])
                ->sortBy(fn (int $order): int => $order)
                ->keys()
                ->values()
                ->all();

            $this->merge(['assignment_ids' => $orders]);
        }
    }
}
