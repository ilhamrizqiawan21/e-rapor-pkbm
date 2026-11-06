<?php

namespace App\Http\Requests\Admin;

use App\Enums\SemesterCode;
use App\Enums\SemesterStatus;
use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSemesterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
            'status' => strtoupper(trim((string) $this->input('status'))),
        ]);
    }

    public function authorize(): bool
    {
        $academicYear = $this->route('academic_year');

        return $academicYear instanceof AcademicYear
            && ($this->user()?->can('create', [Semester::class, $academicYear]) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $academicYear = $this->route('academic_year');

        return [
            'code' => [
                'required',
                Rule::enum(SemesterCode::class),
                Rule::unique('semesters', 'code')->where(
                    fn ($query) => $query->where('academic_year_id', $academicYear?->getKey()),
                ),
            ],
            'name' => ['required', 'string', 'max:100'],
            'sequence' => ['required', 'integer', 'between:1,2'],
            'status' => ['required', Rule::enum(SemesterStatus::class)],
            'confirm_closure' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('confirm_closure', ['accepted'], fn (Fluent $input): bool => ($input->status ?? null) === SemesterStatus::Closed->value);
    }
}
