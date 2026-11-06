<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAttendanceSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reportCard = $this->route('report_card');

        return $reportCard instanceof ReportCard
            && ($this->user()?->can('updateAttendance', $reportCard) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'sick_days' => ['required', 'integer', 'min:0', 'max:366'],
            'leave_days' => ['required', 'integer', 'min:0', 'max:366'],
            'unexcused_days' => ['required', 'integer', 'min:0', 'max:366'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $days = collect(['sick_days', 'leave_days', 'unexcused_days'])
                ->map(fn (string $key): int => (int) $this->input($key, 0));

            if ($days->sum() > 366) {
                $validator->errors()->add('attendance', 'Total hari ketidakhadiran tidak boleh melebihi 366 hari.');
            }
        });
    }
}
