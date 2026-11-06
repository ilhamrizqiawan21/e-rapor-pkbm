<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Services\InstitutionFormatSettingsService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InstitutionPreviewController extends Controller
{
    public function show(Institution $institution, InstitutionFormatSettingsService $settingsService): View
    {
        Gate::authorize('view', $institution);

        return view('admin.institutions.preview', [
            'institution' => $institution,
            'settings' => $settingsService->forInstitution($institution),
            'signatory' => $institution->signatorySetting?->load('headmasterUser.staffProfile'),
        ]);
    }
}
