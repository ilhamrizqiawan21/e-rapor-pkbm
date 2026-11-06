<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleCode;
use App\Enums\SignatorySource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSignatorySettingsRequest;
use App\Models\InstitutionSignatorySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SignatorySettingsController extends Controller
{
    public function edit(): View
    {
        $institution = auth()->user()?->institution;
        abort_if($institution === null, 403);

        $setting = $institution->signatorySetting
            ?? new InstitutionSignatorySetting(['headmaster_source' => SignatorySource::Account]);

        $kepsekUsers = $institution->users()
            ->whereHas('roles', fn ($query) => $query->where('code', RoleCode::Kepsek->value))
            ->with('staffProfile')
            ->orderBy('name')
            ->get();

        return view('admin.signatory-settings.edit', [
            'institution' => $institution,
            'setting' => $setting,
            'kepsekUsers' => $kepsekUsers,
            'sources' => SignatorySource::cases(),
        ]);
    }

    public function update(UpdateSignatorySettingsRequest $request): RedirectResponse
    {
        $institution = $request->user()->institution;
        abort_if($institution === null, 403);

        $validated = $request->validated();
        $isManual = $validated['headmaster_source'] === SignatorySource::Manual->value;

        InstitutionSignatorySetting::query()->updateOrCreate(
            ['institution_id' => $institution->getKey()],
            [
                'headmaster_source' => $validated['headmaster_source'],
                'headmaster_user_id' => $isManual ? null : $validated['headmaster_user_id'],
                'headmaster_name' => $isManual ? $validated['headmaster_name'] : null,
                'headmaster_title' => $isManual ? $validated['headmaster_title'] : null,
                'headmaster_employee_number' => $isManual ? ($validated['headmaster_employee_number'] ?? null) : null,
            ],
        );

        return redirect()->back()->with('success', 'Pengaturan penandatangan rapor berhasil disimpan.');
    }
}
