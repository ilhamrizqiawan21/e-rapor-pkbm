<?php

namespace App\Http\Requests\Admin;

use App\Models\Subject;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/** Reuses subject validation while excluding the current subject from uniqueness. */
class UpdateSubjectRequest extends StoreSubjectRequest
{
    public function authorize(): bool
    {
        $subject = $this->route('subject');

        return $subject instanceof Subject
            && ($this->user()?->can('update', $subject) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $subject = $this->route('subject');

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('subjects', 'code')
                    ->where(fn (Builder $query): Builder => $query->where('institution_id', $this->user()?->institution_id))
                    ->ignore($subject instanceof Subject ? $subject->getKey() : null),
            ],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
            'display_order' => ['required', 'integer', 'min:1', 'max:9999'],
            'active' => ['required', 'boolean'],
        ];
    }
}
