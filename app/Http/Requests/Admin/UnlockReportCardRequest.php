<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;

/** Requires the Admin-only reason recorded when a locked report is reopened. */
class UnlockReportCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reportCard = $this->route('report_card') ?? $this->route('reportCard');

        return $reportCard instanceof ReportCard
            && ($this->user()?->can('unlock', $reportCard) ?? false);
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:3', 'max:2000']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => is_string($this->input('reason')) ? trim($this->input('reason')) : $this->input('reason')]);
    }
}
