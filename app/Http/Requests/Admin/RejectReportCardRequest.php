<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;

/** Validates a workflow rejection reason tied to the routed report card. */
class RejectReportCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reportCard = $this->route('report_card') ?? $this->route('reportCard');

        return $reportCard instanceof ReportCard
            && ($this->user()?->can('reject', $reportCard) ?? false);
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
