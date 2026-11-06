<?php

namespace App\Http\Requests\Admin;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            && ($this->user()?->can('update', $student) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $student = $this->route('student');
        $institutionId = $this->user()?->institution_id;

        return [
            'student_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('students', 'student_number')
                    ->where('institution_id', $institutionId)
                    ->ignore($student),
            ],
            'national_student_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('students', 'national_student_number')
                    ->where('institution_id', $institutionId)
                    ->ignore($student),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:5000'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE', 'GRADUATED', 'TRANSFERRED'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'student_number' => $this->trimmed('student_number'),
            'national_student_number' => $this->nullableTrimmed('national_student_number'),
            'full_name' => $this->trimmed('full_name'),
            'gender' => is_string($this->input('gender')) ? strtoupper(trim($this->input('gender'))) : $this->input('gender'),
            'birth_place' => $this->nullableTrimmed('birth_place'),
            'birth_date' => $this->nullableTrimmed('birth_date'),
            'address' => $this->nullableTrimmed('address'),
            'guardian_name' => $this->nullableTrimmed('guardian_name'),
            'status' => is_string($this->input('status')) ? strtoupper(trim($this->input('status'))) : $this->input('status'),
        ]);
    }

    private function trimmed(string $key): mixed
    {
        return is_string($this->input($key)) ? trim($this->input($key)) : $this->input($key);
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = $this->trimmed($key);

        return $value === null || $value === '' ? null : (string) $value;
    }
}
