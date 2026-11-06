<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleCode;
use App\Enums\SignatorySource;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSignatorySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() === true
            && $this->user()->hasRole(RoleCode::Admin)
            && $this->user()->institution_id !== null;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'headmaster_source' => ['required', Rule::enum(SignatorySource::class)],
            'headmaster_user_id' => ['nullable', 'integer', 'exists:users,id', 'required_if:headmaster_source,ACCOUNT'],
            'headmaster_name' => ['nullable', 'string', 'max:255', 'required_if:headmaster_source,MANUAL'],
            'headmaster_title' => ['nullable', 'string', 'max:255', 'required_if:headmaster_source,MANUAL'],
            'headmaster_employee_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('headmaster_source') !== SignatorySource::Account->value) {
                return;
            }

            $user = User::query()->find($this->integer('headmaster_user_id'));

            if ($user === null
                || $user->institution_id !== $this->user()->institution_id
                || ! $user->isActive()
                || ! $user->hasRole(RoleCode::Kepsek)) {
                $validator->errors()->add('headmaster_user_id', 'Pilih akun Kepala PKBM aktif dari PKBM ini.');
            }
        });
    }
}
