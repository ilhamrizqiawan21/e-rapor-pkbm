<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Validates repeatable extracurricular rows for an editable report card. */
class StoreReportExtracurricularsRequest extends FormRequest
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
            'rows' => ['nullable', 'array', 'max:20'],
            'rows.*.activity_name' => ['required', 'string', 'max:120'],
            'rows.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $rows = collect(is_array($this->input('rows')) ? $this->input('rows') : [])
            ->map(static fn (mixed $row): array => [
                'activity_name' => is_array($row) ? trim((string) ($row['activity_name'] ?? '')) : '',
                'description' => is_array($row) ? trim((string) ($row['description'] ?? '')) : '',
            ])
            ->filter(static fn (array $row): bool => $row['activity_name'] !== '' || $row['description'] !== '')
            ->values()
            ->all();

        $this->merge(['rows' => $rows]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('rows', []) as $index => $row) {
                if (is_array($row) && trim((string) ($row['activity_name'] ?? '')) === '' && trim((string) ($row['description'] ?? '')) !== '') {
                    $validator->errors()->add("rows.{$index}.activity_name", 'Nama kegiatan wajib diisi jika keterangan digunakan.');
                }
            }
        });
    }
}
