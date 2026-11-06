<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/** Allows only Admins to set a strong replacement password for a user account. */
class ResetUserPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleCode::Admin) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Password>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ];
    }
}
