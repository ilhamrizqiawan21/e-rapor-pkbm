<?php

namespace App\Http\Requests\Admin;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAcademicYearRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'status' => strtoupper(trim((string) $this->input('status'))),
        ]);
    }

    public function authorize(): bool
    {
        $academicYear = $this->route('academic_year');

        return $academicYear instanceof AcademicYear
            && ($this->user()?->can('update', $academicYear) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $academicYear = $this->route('academic_year');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('academic_years', 'name')
                    ->where(fn ($query) => $query->where('institution_id', $this->user()->institution_id))
                    ->ignore($academicYear?->getKey()),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(AcademicYearStatus::class)],
            'confirm_closure' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('confirm_closure', ['accepted'], fn (Fluent $input): bool => ($input->status ?? null) === AcademicYearStatus::Closed->value);
    }
}
