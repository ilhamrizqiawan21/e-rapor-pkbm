<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/** Resolves the institution branding used by authenticated and guest layouts. */
final class InstitutionBrandingService
{
    public function resolve(?User $user = null): ?Institution
    {
        $user ??= request()->user();

        if ($user?->institution_id !== null) {
            return $user->institution;
        }

        $configuredInstitutionId = config('app.institution_id');

        if (filled($configuredInstitutionId)) {
            return Institution::query()->find((int) $configuredInstitutionId)
                ?? Institution::query()->oldest('id')->first();
        }

        // Guest pages cannot infer a tenant from a session. For a normal
        // single-PKBM installation, the oldest institution is the safe default;
        // multi-institution deployments can set APP_INSTITUTION_ID explicitly.
        return Institution::query()->oldest('id')->first();
    }

    public function logoUrl(?Institution $institution): ?string
    {
        if ($institution === null || blank($institution->logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($institution->logo_path);
    }
}
