<?php

namespace App\Http\Requests\Admin;

use App\Models\Program;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/** Revalidates program metadata while excluding the current program id. */
class UpdateProgramRequest extends StoreProgramRequest
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
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('programs', 'code')
                    ->where(fn (Builder $query): Builder => $query->where('institution_id', $this->user()?->institution_id))
                    ->ignore($program instanceof Program ? $program->getKey() : null),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
