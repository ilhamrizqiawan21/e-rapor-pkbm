<?php

namespace App\Http\Requests\Admin;

use App\Models\AuditLog;
use Illuminate\Foundation\Http\FormRequest;

class AuditHistoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', AuditLog::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'actor_id' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'entity_type' => ['nullable', 'string', 'in:report_card,report_grade,student,enrollment,institution,user,ledger,backup'],
            'entity_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'action' => filled($this->input('action')) ? trim((string) $this->input('action')) : null,
            'entity_type' => filled($this->input('entity_type')) ? trim((string) $this->input('entity_type')) : null,
        ]);
    }
}
