<?php

namespace App\Http\Requests\Admin;

use App\Models\DescriptionSnippet;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates reusable description text and its institution/private visibility. */
class StoreDescriptionSnippetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DescriptionSnippet::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'subject_id' => [
                'nullable',
                'integer',
                Rule::exists('subjects', 'id')->where(
                    fn (Builder $query): Builder => $query->where('institution_id', $this->user()?->institution_id),
                ),
            ],
            'owner_user_id' => $this->ownerRules(),
            'visibility' => [
                'required',
                Rule::in($this->user()?->hasRole('ADMIN') ? ['SHARED'] : ['PRIVATE']),
            ],
            'active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->trimmed('title'),
            'content' => is_string($this->input('content')) ? trim($this->input('content')) : $this->input('content'),
            'subject_id' => $this->nullableInteger('subject_id'),
            'owner_user_id' => $this->nullableInteger('owner_user_id'),
            'visibility' => is_string($this->input('visibility')) ? strtoupper(trim($this->input('visibility'))) : $this->input('visibility'),
            'active' => $this->has('active') ? $this->boolean('active') : true,
        ]);
    }

    /** @return array<int, mixed> */
    private function ownerRules(): array
    {
        if (! $this->user()?->hasRole('ADMIN')) {
            return ['nullable'];
        }

        return [
            'nullable',
            'integer',
            Rule::exists('users', 'id')->where(function (Builder $query): void {
                $query
                    ->where('institution_id', $this->user()?->institution_id)
                    ->whereIn('id', User::query()
                        ->whereHas('roles', fn ($roles) => $roles->where('code', 'GURU'))
                        ->select('users.id'));
            }),
        ];
    }

    private function trimmed(string $key): mixed
    {
        return is_string($this->input($key)) ? trim($this->input($key)) : $this->input($key);
    }

    private function nullableInteger(string $key): ?int
    {
        $value = $this->input($key);

        return $value === null || $value === '' ? null : (int) $value;
    }
}
