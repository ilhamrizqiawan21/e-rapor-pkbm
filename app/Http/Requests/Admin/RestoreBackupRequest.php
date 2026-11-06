<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

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
                File::types(['sqlite', 'db', 'sql', 'gz'])->max((int) ceil(config('backup.max_restore_bytes', 536870912) / 1024)),
            ],
            'confirmation' => ['required', 'string', 'in:RESTORE'],
        ];
    }
}
