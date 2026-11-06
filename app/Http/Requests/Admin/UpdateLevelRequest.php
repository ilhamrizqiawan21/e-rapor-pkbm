<?php

namespace App\Http\Requests\Admin;

use App\Models\Level;
use App\Models\Program;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/** Revalidates level ownership and uniqueness without changing its parent scope. */
class UpdateLevelRequest extends StoreLevelRequest
{
    public function authorize(): bool
    {
        $program = $this->route('program');
        $level = $this->route('level');

        return $program instanceof Program
            && $level instanceof Level
            && $level->program_id === $program->getKey()
            && ($this->user()?->can('update', $level) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $program = $this->route('program');
        $level = $this->route('level');

        return [
            'number' => [
                'required',
                'integer',
                'between:1,12',
                Rule::unique('levels', 'number')
                    ->where(fn (Builder $query): Builder => $query->where('program_id', $program instanceof Program ? $program->getKey() : 0))
                    ->ignore($level instanceof Level ? $level->getKey() : null),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
