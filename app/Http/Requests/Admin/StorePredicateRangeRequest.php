<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates one score predicate range before overlap checks in the service. */
class StorePredicateRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() === true && $this->user()->hasRole('ADMIN') && $this->user()->institution_id !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('predicate_ranges', 'code')->where(fn ($query) => $query->where('institution_id', $this->user()?->institution_id))],
            'label' => ['required', 'string', 'max:100'],
            'minimum_score' => ['required', 'integer', 'between:0,100'],
            'maximum_score' => ['required', 'integer', 'between:0,100', 'gte:minimum_score'],
            'display_order' => ['required', 'integer', 'min:1', 'max:9999'],
            'active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['active' => $this->boolean('active', true)]);
    }
}
