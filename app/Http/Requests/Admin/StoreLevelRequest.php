<?php

namespace App\Http\Requests\Admin;

use App\Models\Program;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $program = $this->route('program');

        return $program instanceof Program
            && ($this->user()?->can('update', $program) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $program = $this->route('program');

        return [
            'number' => [
                'required',
                'integer',
                'between:1,12',
                Rule::unique('levels', 'number')->where(
                    fn (Builder $query): Builder => $query->where('program_id', $program instanceof Program ? $program->getKey() : 0),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
        ]);
    }
}
