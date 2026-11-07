<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavePeriodPlacementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() && $this->user()->hasRole('ADMIN');
    }

    public function rules(): array
    {
        return ['revision' => ['required', 'integer', 'min:1'], 'configuration_reviewed' => ['sometimes', 'boolean'], 'intent' => ['nullable', 'in:draft,review'],
            'rows' => ['sometimes', 'array'], 'rows.*.class_id' => ['nullable', 'integer'],
            'rows.*.continuation' => ['nullable', 'in:CONTINUE,FINISH'], 'rows.*.decision' => ['prohibited'], 'decision' => ['prohibited']];
    }
}
