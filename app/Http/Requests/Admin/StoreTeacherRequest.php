<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'employee_number' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
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
