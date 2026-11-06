<?php

namespace App\Http\Requests\Admin;

use App\Models\Program;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates configurable Paket/program metadata within the current institution. */
class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Program::class) ?? false;
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
                Rule::unique('programs', 'code')->where(
                    fn (Builder $query): Builder => $query->where('institution_id', $this->user()?->institution_id),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->code) ? strtoupper(trim($this->code)) : $this->code,
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
        ]);
    }
}
