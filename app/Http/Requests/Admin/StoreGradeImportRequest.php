<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;

/** Accepts only the opaque server-side preview token at confirmation time. */
class StoreGradeImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ReportCard::class)
            && $this->user()?->hasAnyRole(['ADMIN', 'GURU']);
    }

    public function rules(): array
    {
        return ['token' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/']];
    }
}
