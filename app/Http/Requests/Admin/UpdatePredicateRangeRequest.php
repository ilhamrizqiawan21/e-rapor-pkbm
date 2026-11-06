<?php

namespace App\Http\Requests\Admin;

use App\Models\PredicateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates an edited predicate range before overlap protection runs. */
class UpdatePredicateRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $range = $this->route('predicate_range');

        return $range instanceof PredicateRange
            && $this->user()?->isActive() === true
            && $this->user()->hasRole('ADMIN')
            && $range->institution_id === $this->user()->institution_id;
    }

    public function rules(): array
    {
        $range = $this->route('predicate_range');

        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('predicate_ranges', 'code')->where(fn ($query) => $query->where('institution_id', $this->user()?->institution_id))->ignore($range)],
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
