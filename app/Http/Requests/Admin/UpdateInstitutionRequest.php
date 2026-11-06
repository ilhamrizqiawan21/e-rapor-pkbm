<?php

namespace App\Http\Requests\Admin;

class UpdateInstitutionRequest extends StoreInstitutionRequest
{
    public function authorize(): bool
    {
        $institution = $this->route('institution');

        return $institution !== null
            && ($this->user()?->can('update', $institution) ?? false);
    }
}
