<?php

namespace App\Http\Requests\Admin;

use App\Enums\SemesterCode;
use App\Enums\SemesterStatus;
use App\Models\Semester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Revalidates semester sequencing and closure rules for the routed period. */
class UpdateSemesterRequest extends FormRequest
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
        $semester = $this->route('semester');

        return $semester instanceof Semester
            && ($this->user()?->can('update', $semester) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $semester = $this->route('semester');

        return [
            'code' => [
                'required',
                Rule::enum(SemesterCode::class),
                Rule::unique('semesters', 'code')
                    ->where(fn ($query) => $query->where('academic_year_id', $semester?->academic_year_id))
                    ->ignore($semester?->getKey()),
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
