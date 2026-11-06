<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StageSixAdminRoutesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_student_and_placement_routes(): void
    {
        $this->get(route('admin.students.index'))->assertRedirect(route('login'));
        $this->get(route('admin.students.import.create'))->assertRedirect(route('login'));
        $this->get(route('admin.academic-classes.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_open_student_import_and_enrollment_routes(): void
    {
        [$institution, $admin, $class] = $this->academicContext();

        $this->actingAs($admin)->get(route('admin.students.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.students.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.students.import.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.students.import.template'))->assertOk();
        $this->actingAs($admin)->get(route('admin.academic-classes.enrollments.index', $class))->assertOk();
        $this->actingAs($admin)->get(route('admin.academic-classes.enrollments.create', $class))->assertOk();

        $this->assertSame($institution->getKey(), $admin->institution_id);
    }

    public function test_guru_can_view_visible_students_but_cannot_manage_or_import(): void
    {
        [, $admin, $class] = $this->academicContext();
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $admin->institution_id]);
        $class->update(['homeroom_teacher_id' => $guru->getKey()]);
        $student = Student::query()->create([
            'institution_id' => $admin->institution_id,
            'student_number' => 'PD-001',
            'full_name' => 'Peserta Terlihat',
            'status' => 'ACTIVE',
        ]);
        $student->enrollments()->create([
            'class_id' => $class->getKey(),
            'academic_year_id' => $class->academic_year_id,
            'start_date' => '2025-07-01',
            'enrollment_status' => 'ACTIVE',
            'reporting_enrollment_id' => null,
        ]);

        $this->actingAs($guru)->get(route('admin.students.index'))->assertOk()->assertSee('Peserta Terlihat');
        $this->actingAs($guru)->get(route('admin.students.create'))->assertForbidden();
        $this->actingAs($guru)->get(route('admin.students.import.create'))->assertForbidden();
    }

    /** @return array{0: Institution, 1: User, 2: AcademicClass} */
    private function academicContext(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Tahap Enam']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->getKey(), 'name' => '2025/2026', 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->getKey(), 'code' => 'PA', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->getKey(), 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create([
            'academic_year_id' => $year->getKey(),
            'program_id' => $program->getKey(),
            'level_id' => $level->getKey(),
            'code' => 'PA-1A',
            'name' => 'Paket A Tingkat 1A',
            'status' => 'ACTIVE',
        ]);

        return [$institution, $admin, $class];
    }
}
