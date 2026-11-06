<?php

namespace App\Http\Requests\Admin;

use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;

/** Validates the dated, reasoned withdrawal of a routed enrollment. */
class WithdrawEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enrollment = $this->route('enrollment');

        return $enrollment instanceof Enrollment
            && ($this->user()?->can('update', $enrollment) ?? false);
    }

    public function rules(): array
    {
        return [
            'end_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
