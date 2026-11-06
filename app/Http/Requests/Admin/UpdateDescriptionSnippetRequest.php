<?php

namespace App\Http\Requests\Admin;

use App\Models\DescriptionSnippet;

/** Applies the same visibility contract as creation while excluding its own id. */
class UpdateDescriptionSnippetRequest extends StoreDescriptionSnippetRequest
{
    public function authorize(): bool
    {
        $snippet = $this->route('description_snippet');

        return $snippet instanceof DescriptionSnippet
            && ($this->user()?->can('update', $snippet) ?? false);
    }
}
