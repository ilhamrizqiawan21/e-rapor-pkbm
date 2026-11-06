<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReportCardIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reportCard = $this->route('report_card');

        return $reportCard instanceof ReportCard
            && ($this->user()?->can('manageCompleteness', $reportCard) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'document_number' => [
                'nullable',
                'string',
                'max:100',
                'regex:/\A[A-Za-z0-9][A-Za-z0-9 ._\/-]*\z/',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('document_number');

        $this->merge([
            'document_number' => is_string($value) && trim($value) !== '' ? trim($value) : null,
        ]);
    }
}
