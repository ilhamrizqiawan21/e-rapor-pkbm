<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StageFourAdminRoutesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_period_program_and_class_routes(): void
    {
        foreach ([
            route('admin.academic-years.index'),
            route('admin.programs.index'),
            route('admin.academic-classes.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_admin_can_open_stage_four_management_routes(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Tahap Empat']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);
        $program = Program::query()->create([
            'institution_id' => $institution->getKey(),
            'code' => 'PA',
            'name' => 'Paket A',
        ]);
        $level = Level::query()->create(['program_id' => $program->getKey(), 'number' => 1, 'name' => 'Tingkat 1']);
        $academicYear = AcademicYear::query()->create([
            'institution_id' => $institution->getKey(),
            'name' => '2025/2026',
            'status' => 'DRAFT',
        ]);

        $this->actingAs($admin)->get(route('admin.academic-years.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.academic-years.show', $academicYear))->assertOk();
        $this->actingAs($admin)->get(route('admin.programs.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.programs.show', $program))->assertOk();
        $this->actingAs($admin)->get(route('admin.programs.levels.show', [$program, $level]))->assertOk();
        $this->actingAs($admin)->get(route('admin.academic-classes.index'))->assertOk();
    }

    public function test_guru_can_view_but_cannot_manage_stage_four_master_data(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Guru Tahap Empat']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey()]);

        foreach ([
            route('admin.academic-years.index'),
            route('admin.programs.index'),
            route('admin.academic-classes.index'),
        ] as $url) {
            $this->actingAs($guru)->get($url)->assertOk();
        }

        foreach ([
            route('admin.academic-years.create'),
            route('admin.programs.create'),
            route('admin.academic-classes.create'),
        ] as $url) {
            $this->actingAs($guru)->get($url)->assertForbidden();
        }
    }

    public function test_guru_only_sees_academic_years_with_an_assignment(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Scope Tahap Empat']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey()]);
        $program = Program::query()->create([
            'institution_id' => $institution->getKey(),
            'code' => 'PA',
            'name' => 'Paket A',
        ]);
        $level = Level::query()->create(['program_id' => $program->getKey(), 'number' => 1, 'name' => 'Tingkat 1']);
        $assignedYear = AcademicYear::query()->create([
            'institution_id' => $institution->getKey(),
            'name' => '2025/2026',
            'status' => 'DRAFT',
        ]);
        $unassignedYear = AcademicYear::query()->create([
            'institution_id' => $institution->getKey(),
            'name' => '2026/2027',
            'status' => 'DRAFT',
        ]);
        AcademicClass::query()->create([
            'academic_year_id' => $assignedYear->getKey(),
            'program_id' => $program->getKey(),
            'level_id' => $level->getKey(),
            'homeroom_teacher_id' => $guru->getKey(),
            'code' => 'PA-1A',
            'name' => 'Paket A Tingkat 1A',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($guru)
            ->get(route('admin.academic-years.index'))
            ->assertOk()
            ->assertSee('2025/2026')
            ->assertDontSee('2026/2027');
        $this->actingAs($guru)->get(route('admin.academic-years.show', $assignedYear))->assertOk();
        $this->actingAs($guru)->get(route('admin.academic-years.show', $unassignedYear))->assertForbidden();
    }
}
