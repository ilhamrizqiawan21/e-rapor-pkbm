<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InstitutionAdminRoutesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_all_stage_three_admin_routes(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Guest']);

        foreach ([
            route('admin.institutions.index'),
            route('admin.institutions.edit', $institution),
            route('admin.signatory-settings.edit'),
            route('admin.settings.format.edit'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_admin_can_open_stage_three_management_routes(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Admin']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create([
            'institution_id' => $institution->getKey(),
        ]);

        $this->actingAs($admin)->get(route('admin.institutions.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.institutions.edit', $institution))->assertOk();
        $this->actingAs($admin)->get(route('admin.signatory-settings.edit'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.format.edit'))->assertOk();
    }

    public function test_non_admin_cannot_open_stage_three_admin_routes(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Guru']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create([
            'institution_id' => $institution->getKey(),
        ]);

        foreach ([
            route('admin.institutions.index'),
            route('admin.institutions.edit', $institution),
            route('admin.signatory-settings.edit'),
            route('admin.settings.format.edit'),
        ] as $url) {
            $this->actingAs($guru)->get($url)->assertForbidden();
        }
    }
}
