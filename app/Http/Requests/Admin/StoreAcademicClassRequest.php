<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicClass;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAcademicClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AcademicClass::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $institutionId = $this->user()?->institution_id;
        $academicYearId = $this->input('academic_year_id');
        $programId = $this->input('program_id');

        return [
            'academic_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id')->where(fn ($query) => $query->where('institution_id', $institutionId)),
            ],
            'program_id' => [
                'required',
                'integer',
                Rule::exists('programs', 'id')->where(fn ($query) => $query->where('institution_id', $institutionId)),
            ],
            'level_id' => [
                'required',
                'integer',
                Rule::exists('levels', 'id')->where(fn ($query) => $query->where('program_id', $programId)),
            ],
            'homeroom_teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($institutionId): void {
                    $query->where('institution_id', $institutionId)
                        ->where('status', 'ACTIVE');
                }),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9._\/-]+$/',
                Rule::unique('academic_classes', 'code')->where(fn ($query) => $query->where('academic_year_id', $academicYearId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->code) ? strtoupper(trim($this->code)) : $this->code,
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
        ]);
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('homeroom_teacher_id') && ! User::query()
                ->whereKey($this->input('homeroom_teacher_id'))
                ->where('institution_id', $this->user()?->institution_id)
                ->where('status', 'ACTIVE')
                ->whereHas('roles', fn ($roles) => $roles->where('code', 'GURU'))
                ->exists()) {
                $validator->errors()->add('homeroom_teacher_id', 'Wali kelas harus Guru aktif dari institusi yang sama.');
            }
        });
    }
}
