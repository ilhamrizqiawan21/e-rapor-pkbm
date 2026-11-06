<?php

namespace App\Http\Requests\Admin;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

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
