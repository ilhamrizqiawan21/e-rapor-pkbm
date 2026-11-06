<?php

namespace App\Http\Requests\Admin;

/** Reuses tenant identity validation while excluding the current institution. */
class UpdateInstitutionRequest extends StoreInstitutionRequest
{
    public function authorize(): bool
    {
        $institution = $this->route('institution');

        return $institution !== null
            && ($this->user()?->can('update', $institution) ?? false);
    }
}
