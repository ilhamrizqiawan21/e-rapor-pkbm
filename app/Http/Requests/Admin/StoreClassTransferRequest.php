<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicClass;
use App\Models\ClassTransfer;
use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Validates same-institution, same-year class transfers approved by Admin. */
class StoreClassTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enrollment = $this->route('enrollment');

        return $enrollment instanceof Enrollment
            && ($this->user()?->can('create', ClassTransfer::class) ?? false)
            && ($this->user()?->can('view', $enrollment) ?? false);
    }

    public function rules(): array
    {
        $enrollment = $this->route('enrollment');

        return [
            'target_class_id' => [
                'required',
                'integer',
                Rule::exists('academic_classes', 'id'),
            ],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reporting_enrollment_id' => [
                'nullable',
                Rule::in([(string) $enrollment?->getKey(), 'target']),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reporting_enrollment_id' => $this->input('reporting_enrollment_id', 'target'),
        ]);
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $enrollment = $this->route('enrollment');
            $target = AcademicClass::query()->with('academicYear')->find($this->input('target_class_id'));

            if (! $target instanceof AcademicClass || $target->program?->institution_id !== $this->user()?->institution_id) {
                $validator->errors()->add('target_class_id', 'Kelas tujuan harus berasal dari institusi yang sama.');
            }

            if ($enrollment instanceof Enrollment && $target instanceof AcademicClass
                && (int) $target->academic_year_id !== (int) $enrollment->academic_year_id) {
                $validator->errors()->add('target_class_id', 'Kelas tujuan harus berada pada tahun pelajaran yang sama.');
            }

            if ($this->user()?->hasRole('ADMIN') !== true) {
                $validator->errors()->add('target_class_id', 'Hanya Admin yang dapat menyetujui perpindahan kelas.');
            }
        });
    }
}
