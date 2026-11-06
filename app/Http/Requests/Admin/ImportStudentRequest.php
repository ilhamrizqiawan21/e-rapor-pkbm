<?php

namespace App\Http\Requests\Admin;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

class ImportStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Student::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'preview_token' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
        ];
    }
}
