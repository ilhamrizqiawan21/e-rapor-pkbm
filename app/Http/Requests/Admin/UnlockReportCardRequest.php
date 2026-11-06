<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;

class UnlockReportCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reportCard = $this->route('reportCard');

        return $reportCard instanceof ReportCard
            && ($this->user()?->can('unlock', $reportCard) ?? false);
    }

    public function rules(): array
    {
        return ['reason' => ['nullable', 'string', 'max:2000']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => is_string($this->input('reason')) ? trim($this->input('reason')) : $this->input('reason')]);
    }
}
