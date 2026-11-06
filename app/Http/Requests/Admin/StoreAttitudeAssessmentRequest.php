<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates the two supported attitude dimensions on an editable report card. */
class StoreAttitudeAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reportCard = $this->route('report_card');

        return $reportCard instanceof ReportCard
            && ($this->user()?->can('updateAttitude', $reportCard) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'attitude_type' => is_string($this->input('attitude_type'))
                ? strtoupper(trim($this->input('attitude_type')))
                : $this->input('attitude_type'),
            'description' => is_string($this->input('description'))
                ? trim($this->input('description'))
                : $this->input('description'),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'attitude_type' => ['required', 'string', Rule::in(['SPIRITUAL', 'SOCIAL'])],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
