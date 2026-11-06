<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstitutionRequest;
use App\Http\Requests\Admin\UpdateInstitutionRequest;
use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    public function index(Request $request): View
    {
        $institution = $request->user()?->institution;

        abort_unless(
            $institution !== null || $request->user()?->can('create', Institution::class),
            403,
        );

        if ($institution !== null) {
            Gate::authorize('view', $institution);
        }

        return view('admin.institutions.index', compact('institution'));
    }

    public function create(): View
    {
        Gate::authorize('create', Institution::class);

        return view('admin.institutions.create', ['institution' => new Institution]);
    }

    public function store(StoreInstitutionRequest $request): RedirectResponse
    {
        Gate::authorize('create', Institution::class);

        $institution = Institution::query()->create(Arr::except($request->validated(), ['logo', 'remove_logo']));

        if ($request->hasFile('logo')) {
            $institution->update(['logo_path' => $this->storeLogo($institution, $request->file('logo'))]);
        }

        if ($request->user()->institution_id === null) {
            $request->user()->update(['institution_id' => $institution->getKey()]);
        }

        return redirect()
            ->route('admin.institutions.edit', $institution)
            ->with('status', 'Identitas PKBM berhasil dibuat.');
    }

    public function show(Institution $institution): View
    {
        Gate::authorize('view', $institution);

        return view('admin.institutions.show', compact('institution'));
    }

    public function edit(Institution $institution): View
    {
        Gate::authorize('update', $institution);

        return view('admin.institutions.edit', compact('institution'));
    }

    public function update(UpdateInstitutionRequest $request, Institution $institution): RedirectResponse
    {
        Gate::authorize('update', $institution);

        $oldLogoPath = $institution->logo_path;
        $institution->update(Arr::except($request->validated(), ['logo', 'remove_logo']));

        if ($request->hasFile('logo')) {
            $newLogoPath = $this->storeLogo($institution, $request->file('logo'));
            $institution->update(['logo_path' => $newLogoPath]);

            $this->deleteLogo($oldLogoPath, $newLogoPath);
        } elseif ($request->boolean('remove_logo') && $oldLogoPath !== null) {
            $institution->update(['logo_path' => null]);
            $this->deleteLogo($oldLogoPath);
        }

        return redirect()
            ->route('admin.institutions.edit', $institution)
            ->with('status', 'Identitas PKBM berhasil diperbarui.');
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        Gate::authorize('delete', $institution);

        $logoPath = $institution->logo_path;
        $institution->delete();
        $this->deleteLogo($logoPath);

        return redirect()
            ->route('admin.institutions.index')
            ->with('status', 'Identitas PKBM berhasil dihapus.');
    }

    public function destroyLogo(Request $request, Institution $institution): RedirectResponse
    {
        Gate::authorize('update', $institution);

        $this->deleteLogo($institution->logo_path);
        $institution->update(['logo_path' => null]);

        return redirect()
            ->route('admin.institutions.edit', $institution)
            ->with('status', 'Logo PKBM berhasil dihapus.');
    }

    private function storeLogo(Institution $institution, mixed $logo): string
    {
        $path = $logo->store('institutions/'.$institution->getKey(), 'public');

        abort_unless(is_string($path), 500, 'Logo PKBM gagal disimpan.');

        return $path;
    }

    private function deleteLogo(?string $oldPath, ?string $newPath = null): void
    {
        if ($oldPath !== null && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}
