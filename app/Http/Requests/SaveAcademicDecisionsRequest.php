<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveAcademicDecisionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() && $this->user()->hasRole('GURU');
    }

    public function rules(): array
    {
        return ['rows' => ['required', 'array'], 'rows.*.decision' => ['nullable', 'in:PROMOTE,REPEAT,GRADUATE,NOT_GRADUATED'],
            'rows.*.revision' => ['required', 'integer', 'min:0']];
    }
}
