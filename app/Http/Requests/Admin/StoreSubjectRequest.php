<?php

namespace App\Http\Requests\Admin;

use App\Models\Subject;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates customizable subject metadata scoped to the current institution. */
class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Subject::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('subjects', 'code')->where(
                    fn (Builder $query): Builder => $query->where('institution_id', $this->user()?->institution_id),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
            'display_order' => ['required', 'integer', 'min:1', 'max:9999'],
            'active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->code) ? strtoupper(trim($this->code)) : $this->code,
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'short_name' => is_string($this->short_name) ? strtoupper(trim($this->short_name)) : $this->short_name,
            'category' => is_string($this->category) ? trim($this->category) : $this->category,
            'active' => $this->has('active') ? $this->boolean('active') : true,
        ]);
    }
}
