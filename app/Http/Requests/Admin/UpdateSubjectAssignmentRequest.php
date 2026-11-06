<?php

namespace App\Http\Requests\Admin;

use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSubjectAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subjectAssignment = $this->route('subject_assignment');

        return $subjectAssignment instanceof SubjectAssignment
            && ($this->user()?->can('update', $subjectAssignment) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $institutionId = $this->user()?->institution_id;
        $academicClass = $this->route('academic_class');
        $subjectAssignment = $this->route('subject_assignment');

        return [
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where(fn ($query) => $query
                    ->where('institution_id', $institutionId)
                    ->where('active', true)),
                Rule::unique('subject_assignments', 'subject_id')
                    ->where(fn ($query) => $query->where('class_id', $academicClass?->getKey()))
                    ->ignore($subjectAssignment),
            ],
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('institution_id', $institutionId)
                    ->where('status', 'ACTIVE')),
            ],
            'minimum_score' => ['required', 'integer', 'between:0,100'],
            'display_order' => ['required', 'integer', 'min:1', 'max:9999'],
            'active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $academicClass = $this->route('academic_class');
        $academicClass?->loadMissing('academicYear');

        abort_if(
            $academicClass?->academicYear?->status?->value === 'CLOSED',
            422,
            'Periode akademik sudah ditutup dan tidak menerima perubahan penugasan mata pelajaran.',
        );

        $this->merge(['active' => $this->boolean('active', true)]);
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('teacher_id') && ! User::query()
                ->whereKey($this->input('teacher_id'))
                ->where('institution_id', $this->user()?->institution_id)
                ->where('status', 'ACTIVE')
                ->whereHas('roles', fn ($roles) => $roles->where('code', 'GURU'))
                ->exists()) {
                $validator->errors()->add('teacher_id', 'Guru harus aktif dan berasal dari institusi yang sama.');
            }
        });
    }
}
