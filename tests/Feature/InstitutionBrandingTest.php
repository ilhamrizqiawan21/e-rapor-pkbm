<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstitutionBrandingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_layout_uses_the_configured_institution_logo_and_favicon(): void
    {
        Storage::fake('public');
        $institution = Institution::query()->create([
            'name' => 'PKBM Branding',
            'logo_path' => 'institutions/1/logo.png',
        ]);
        Storage::disk('public')->put($institution->logo_path, 'logo');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Logo PKBM Branding', false)
            ->assertSee('storage/institutions/1/logo.png', false)
            ->assertSee('<link rel="icon"', false);
    }

    public function test_authenticated_layout_uses_the_current_institution_logo(): void
    {
        Storage::fake('public');
        $institution = Institution::query()->create([
            'name' => 'PKBM Dashboard Branding',
            'logo_path' => 'institutions/2/logo.png',
        ]);
        $admin = User::factory()->withRole(RoleCode::Admin)->create([
            'institution_id' => $institution->getKey(),
        ]);
        Storage::disk('public')->put($institution->logo_path, 'logo');

        $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('Logo PKBM Dashboard Branding', false)
            ->assertSee('storage/institutions/2/logo.png', false);
    }
}
