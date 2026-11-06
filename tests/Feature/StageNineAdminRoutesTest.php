<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StageNineAdminRoutesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_grade_entry(): void
    {
        $this->get(route('admin.grade-entry.index'))->assertRedirect(route('login'));
    }

    public function test_admin_guru_and_kepsek_can_open_grade_entry_context(): void
    {
        foreach ([RoleCode::Admin, RoleCode::Guru, RoleCode::Kepsek] as $role) {
            $user = $this->user($role);

            $this->actingAs($user)
                ->get(route('admin.grade-entry.index'))
                ->assertOk()
                ->assertSee('Input nilai');
        }
    }

    private function user(RoleCode $role): User
    {
        $institution = Institution::query()->create(['name' => 'PKBM Stage Nine']);

        return User::factory()->withRole($role)->create(['institution_id' => $institution->id]);
    }
}
