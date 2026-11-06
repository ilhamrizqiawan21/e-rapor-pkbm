<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StageEightAdminRoutesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_description_bank_and_picker(): void
    {
        $this->get(route('admin.description-snippets.index'))->assertRedirect(route('login'));
        $this->get(route('admin.description-snippets.picker'))->assertRedirect(route('login'));
    }

    public function test_admin_can_open_description_bank_and_json_picker(): void
    {
        $admin = $this->user(RoleCode::Admin);

        $this->actingAs($admin)
            ->get(route('admin.description-snippets.index'))
            ->assertOk()
            ->assertSee('Bank deskripsi');

        $this->actingAs($admin)
            ->get(route('admin.description-snippets.picker', ['format' => 'json']))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'filters']);
    }

    public function test_guru_can_open_picker_and_private_snippet_form(): void
    {
        $guru = $this->user(RoleCode::Guru);

        $this->actingAs($guru)->get(route('admin.description-snippets.picker'))->assertOk();
        $this->actingAs($guru)->get(route('admin.description-snippets.create'))->assertOk();
    }

    private function user(RoleCode $role): User
    {
        $institution = Institution::query()->create(['name' => 'PKBM Stage Eight']);

        return User::factory()->withRole($role)->create(['institution_id' => $institution->id]);
    }
}
