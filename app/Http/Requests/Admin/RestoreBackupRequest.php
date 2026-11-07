<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Requires an Admin and explicit PULIHKAN confirmation for destructive recovery. */
final class RestoreBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('ADMIN') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'backup' => [
                'required',
                'file',
                'max:'.(int) ceil(config('backup.max_restore_bytes', 536870912) / 1024),
                'extensions:sqlite,db,gz,enc',
            ],
            'confirmation' => ['required', 'string', 'in:PULIHKAN'],
        ];
    }
}
