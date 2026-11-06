<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('user');

        return $teacher instanceof User
            && ($this->user()?->can('update', $teacher) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $teacher = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($teacher)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher)],
            'employee_number' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(array_column(UserStatus::cases(), 'value'))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->trimmed('name'),
            'username' => $this->lowercaseTrimmed('username'),
            'email' => $this->nullableLowercaseTrimmed('email'),
            'employee_number' => $this->nullableTrimmed('employee_number'),
            'title' => $this->nullableTrimmed('title'),
            'phone' => $this->nullableTrimmed('phone'),
        ]);
    }

    private function trimmed(string $key): mixed
    {
        return is_string($this->input($key)) ? trim($this->input($key)) : $this->input($key);
    }

    private function lowercaseTrimmed(string $key): mixed
    {
        $value = $this->trimmed($key);

        return is_string($value) ? strtolower($value) : $value;
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = $this->trimmed($key);

        return $value === '' || $value === null ? null : (string) $value;
    }

    private function nullableLowercaseTrimmed(string $key): ?string
    {
        $value = $this->nullableTrimmed($key);

        return $value === null ? null : strtolower($value);
    }
}
