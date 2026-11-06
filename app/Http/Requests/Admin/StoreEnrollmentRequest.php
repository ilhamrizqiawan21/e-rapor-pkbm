<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicClass;
use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academicClass = $this->route('academic_class');

        return $academicClass instanceof AcademicClass
            && ($this->user()?->can('create', Enrollment::class) ?? false)
            && ($this->user()?->can('view', $academicClass) ?? false);
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where(fn ($query) => $query->where('institution_id', $this->user()?->institution_id)->where('status', 'ACTIVE')),
            ],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
