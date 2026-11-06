<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PreviewPredicateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() === true && $this->user()->institution_id !== null;
    }

    public function rules(): array
    {
        return ['score' => ['nullable', 'integer', 'between:0,100']];
    }
}
