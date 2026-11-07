<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavePeriodPreparationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() && $this->user()->hasRole('ADMIN');
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'], 'revision' => ['required', 'integer', 'min:0'],
            'decision' => ['prohibited'], 'rows' => ['prohibited']];
    }
}
