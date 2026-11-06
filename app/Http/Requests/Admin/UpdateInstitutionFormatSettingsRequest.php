<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates institution-owned report formatting and display preferences. */
class UpdateInstitutionFormatSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->institution !== null && $user->can('update', $user->institution);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_logo' => $this->boolean('show_logo'),
            'show_kkm' => $this->boolean('show_kkm'),
            'show_predicate' => $this->boolean('show_predicate'),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'student_name_format' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_{}.,\-\/\\s]+$/'],
            'document_number_format' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_{}.,\-\/\\s]+$/'],
            'date_format' => ['required', 'string', 'max:32', 'regex:/^[dDjlNStyFmMnY\-\/.,\s]+$/'],
            'signature_left_role' => ['required', Rule::in(['ADMIN', 'GURU', 'KEPSEK'])],
            'signature_left_label' => ['required', 'string', 'max:100'],
            'signature_right_role' => ['required', Rule::in(['ADMIN', 'GURU', 'KEPSEK'])],
            'signature_right_label' => ['required', 'string', 'max:100'],
            'show_logo' => ['required', 'boolean'],
            'logo_position' => ['required', Rule::in(['left', 'center', 'right'])],
            'logo_width_mm' => ['required', 'integer', 'min:10', 'max:60'],
            'margin_top_mm' => ['required', 'integer', 'min:5', 'max:40'],
            'margin_right_mm' => ['required', 'integer', 'min:5', 'max:40'],
            'margin_bottom_mm' => ['required', 'integer', 'min:5', 'max:40'],
            'margin_left_mm' => ['required', 'integer', 'min:5', 'max:40'],
            'header_text' => ['nullable', 'string', 'max:500'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'show_kkm' => ['required', 'boolean'],
            'show_predicate' => ['required', 'boolean'],
        ];
    }
}
