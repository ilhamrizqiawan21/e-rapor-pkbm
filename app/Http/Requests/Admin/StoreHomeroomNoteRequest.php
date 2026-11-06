<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use App\Services\ReportCardExtrasService;
use Illuminate\Foundation\Http\FormRequest;

class StoreHomeroomNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reportCard = $this->route('reportCard') ?? $this->route('report_card');

        return $reportCard instanceof ReportCard
            && ($this->user()?->can('manageCompleteness', $reportCard) ?? false);
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:'.ReportCardExtrasService::HOMEROOM_NOTE_MAX_LENGTH],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => is_string($this->input('note')) ? trim($this->input('note')) : $this->input('note'),
        ]);
    }
}
