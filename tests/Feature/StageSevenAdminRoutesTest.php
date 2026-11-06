<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StageSevenAdminRoutesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_stage_seven_entry_points(): void
    {
        $this->get(route('admin.subjects.index'))->assertRedirect(route('login'));
        $this->get(route('admin.predicate-ranges.index'))->assertRedirect(route('login'));
        $this->get(route('admin.academic-classes.subject-assignments.bulk-create'))->assertRedirect(route('login'));
    }

    public function test_admin_can_open_subject_predicate_and_mapping_tools(): void
    {
        $admin = $this->user(RoleCode::Admin);

        $this->actingAs($admin)->get(route('admin.subjects.index'))->assertOk()->assertSee('Mata pelajaran');
        $this->actingAs($admin)->get(route('admin.predicate-ranges.index'))->assertOk()->assertSee('Rentang predikat');
        $this->actingAs($admin)->get(route('admin.academic-classes.subject-assignments.bulk-create'))->assertOk()->assertSee('Tambah mapel ke beberapa kelas');
    }

    public function test_guru_can_read_subject_master_but_cannot_change_predicates_or_bulk_mapping(): void
    {
        $guru = $this->user(RoleCode::Guru);

        $this->actingAs($guru)->get(route('admin.subjects.index'))->assertOk();
        $this->actingAs($guru)->get(route('admin.predicate-ranges.index'))->assertForbidden();
        $this->actingAs($guru)->get(route('admin.academic-classes.subject-assignments.bulk-create'))->assertForbidden();
    }

    private function user(RoleCode $role): User
    {
        $institution = Institution::query()->create(['name' => 'PKBM Stage Seven']);

        return User::factory()->withRole($role)->create(['institution_id' => $institution->id]);
    }
}
