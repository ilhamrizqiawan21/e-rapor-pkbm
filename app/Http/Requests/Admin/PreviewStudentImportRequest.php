<?php

namespace App\Http\Requests\Admin;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

/** Gates student-file preview to Admins and the approved spreadsheet formats. */
class PreviewStudentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Student::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'extensions:xlsx,csv,txt'],
        ];
    }
}
