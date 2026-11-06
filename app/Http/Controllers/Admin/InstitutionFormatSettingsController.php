<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateInstitutionFormatSettingsRequest;
use App\Services\InstitutionFormatSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstitutionFormatSettingsController extends Controller
{
    public function edit(Request $request, InstitutionFormatSettingsService $settingsService): View
    {
        $institution = $request->user()?->institution;
        abort_unless($institution, 404);
        abort_unless($request->user()?->can('view', $institution), 403);

        return view('admin.settings.format', [
            'settings' => $settingsService->forInstitution($institution),
        ]);
    }

    public function update(
        UpdateInstitutionFormatSettingsRequest $request,
        InstitutionFormatSettingsService $settingsService,
    ): RedirectResponse {
        $institution = $request->user()?->institution;
        abort_unless($institution, 404);
        abort_unless($request->user()?->can('update', $institution), 403);

        $settingsService->updateForInstitution($institution, $request->validated());

        return back()->with('status', 'Pengaturan format rapor berhasil disimpan.');
    }
}
