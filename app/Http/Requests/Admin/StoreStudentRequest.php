<?php

namespace App\Http\Requests\Admin;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates student PII and tenant-unique identifiers for creation. */
class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Student::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $institutionId = $this->user()?->institution_id;

        return [
            'student_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('students', 'student_number')->where('institution_id', $institutionId),
            ],
            'national_student_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('students', 'national_student_number')->where('institution_id', $institutionId),
            ],
            'nik' => ['nullable', 'string', 'max:20'],
            'family_card_number' => ['nullable', 'string', 'max:20'],
            'full_name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'religion' => ['nullable', 'string', 'max:50'],
            'citizenship' => ['nullable', 'string', 'max:50'],
            'birth_order' => ['nullable', 'integer', 'min:1', 'max:99'],
            'siblings_count' => ['nullable', 'integer', 'min:0', 'max:99'],
            'address' => ['nullable', 'string', 'max:5000'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'living_with' => ['nullable', 'string', 'max:50'],
            'transportation_mode' => ['nullable', 'string', 'max:50'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'father_nik' => ['nullable', 'string', 'max:20'],
            'father_birth_place' => ['nullable', 'string', 'max:100'],
            'father_birth_date' => ['nullable', 'date'],
            'father_education' => ['nullable', 'string', 'max:50'],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'father_income' => ['nullable', 'string', 'max:100'],
            'father_phone' => ['nullable', 'string', 'max:30'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_nik' => ['nullable', 'string', 'max:20'],
            'mother_birth_place' => ['nullable', 'string', 'max:100'],
            'mother_birth_date' => ['nullable', 'date'],
            'mother_education' => ['nullable', 'string', 'max:50'],
            'mother_occupation' => ['nullable', 'string', 'max:100'],
            'mother_income' => ['nullable', 'string', 'max:100'],
            'mother_phone' => ['nullable', 'string', 'max:30'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_relation' => ['nullable', 'string', 'max:50'],
            'guardian_occupation' => ['nullable', 'string', 'max:100'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'previous_school_name' => ['nullable', 'string', 'max:255'],
            'previous_diploma_number' => ['nullable', 'string', 'max:100'],
            'entry_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE', 'GRADUATED', 'TRANSFERRED'])],
            'graduation_certificate_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'student_number' => $this->trimmed('student_number'),
            'national_student_number' => $this->nullableTrimmed('national_student_number'),
            'nik' => $this->nullableTrimmed('nik'),
            'family_card_number' => $this->nullableTrimmed('family_card_number'),
            'full_name' => $this->trimmed('full_name'),
            'nickname' => $this->nullableTrimmed('nickname'),
            'gender' => is_string($this->input('gender')) ? strtoupper(trim($this->input('gender'))) : $this->input('gender'),
            'birth_place' => $this->nullableTrimmed('birth_place'),
            'birth_date' => $this->nullableTrimmed('birth_date'),
            'religion' => $this->nullableTrimmed('religion'),
            'citizenship' => $this->nullableTrimmed('citizenship'),
            'address' => $this->nullableTrimmed('address'),
            'postal_code' => $this->nullableTrimmed('postal_code'),
            'phone_number' => $this->nullableTrimmed('phone_number'),
            'living_with' => $this->nullableTrimmed('living_with'),
            'transportation_mode' => $this->nullableTrimmed('transportation_mode'),
            'father_name' => $this->nullableTrimmed('father_name'),
            'father_nik' => $this->nullableTrimmed('father_nik'),
            'father_birth_place' => $this->nullableTrimmed('father_birth_place'),
            'father_birth_date' => $this->nullableTrimmed('father_birth_date'),
            'father_education' => $this->nullableTrimmed('father_education'),
            'father_occupation' => $this->nullableTrimmed('father_occupation'),
            'father_income' => $this->nullableTrimmed('father_income'),
            'father_phone' => $this->nullableTrimmed('father_phone'),
            'mother_name' => $this->nullableTrimmed('mother_name'),
            'mother_nik' => $this->nullableTrimmed('mother_nik'),
            'mother_birth_place' => $this->nullableTrimmed('mother_birth_place'),
            'mother_birth_date' => $this->nullableTrimmed('mother_birth_date'),
            'mother_education' => $this->nullableTrimmed('mother_education'),
            'mother_occupation' => $this->nullableTrimmed('mother_occupation'),
            'mother_income' => $this->nullableTrimmed('mother_income'),
            'mother_phone' => $this->nullableTrimmed('mother_phone'),
            'guardian_name' => $this->nullableTrimmed('guardian_name'),
            'guardian_relation' => $this->nullableTrimmed('guardian_relation'),
            'guardian_occupation' => $this->nullableTrimmed('guardian_occupation'),
            'guardian_phone' => $this->nullableTrimmed('guardian_phone'),
            'previous_school_name' => $this->nullableTrimmed('previous_school_name'),
            'previous_diploma_number' => $this->nullableTrimmed('previous_diploma_number'),
            'entry_date' => $this->nullableTrimmed('entry_date'),
            'status' => is_string($this->input('status')) ? strtoupper(trim($this->input('status'))) : $this->input('status'),
            'graduation_certificate_number' => $this->nullableTrimmed('graduation_certificate_number'),
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
