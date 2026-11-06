<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use Illuminate\Foundation\Http\FormRequest;

class StoreInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Institution::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'postal_code' => ['nullable', 'string', 'max:10', 'regex:/^[0-9]{5,10}$/'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'npsn' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-\/]+$/'],
            'permit_number' => ['nullable', 'string', 'max:100'],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:min_width=100,min_height=100,max_width=2400,max_height=2400',
            ],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'address' => is_string($this->address) ? trim($this->address) : $this->address,
            'postal_code' => is_string($this->postal_code) ? trim($this->postal_code) : $this->postal_code,
            'phone' => is_string($this->phone) ? trim($this->phone) : $this->phone,
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email,
            'npsn' => is_string($this->npsn) ? strtoupper(trim($this->npsn)) : $this->npsn,
            'permit_number' => is_string($this->permit_number) ? trim($this->permit_number) : $this->permit_number,
        ]);
    }
}
