<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StageFiveAdminRoutesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_teacher_and_assignment_routes(): void
    {
        foreach ([
            route('admin.teachers.index'),
            route('admin.academic-classes.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_admin_can_open_teacher_and_assignment_management_routes(): void
    {
        [$institution, $admin, $class, $subject] = $this->academicContext();

        $this->actingAs($admin)->get(route('admin.teachers.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.teachers.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.academic-classes.subject-assignments.index', $class))->assertOk();
        $this->actingAs($admin)->get(route('admin.academic-classes.subject-assignments.create', $class))->assertOk();

        $this->assertSame($institution->getKey(), $subject->institution_id);
    }

    public function test_guru_only_sees_own_assignment(): void
    {
        [, $admin, $class, $subject] = $this->academicContext();
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $admin->institution_id, 'name' => 'Guru Assignment']);
        $otherTeacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $admin->institution_id, 'name' => 'Guru Lain']);
        SubjectAssignment::query()->create([
            'class_id' => $class->getKey(),
            'subject_id' => $subject->getKey(),
            'teacher_id' => $teacher->getKey(),
            'minimum_score' => 70,
            'display_order' => 1,
            'active' => true,
        ]);
        $otherSubject = Subject::query()->create([
            'institution_id' => $admin->institution_id,
            'code' => 'BIN',
            'name' => 'Bahasa Indonesia',
            'short_name' => 'BIN',
            'active' => true,
        ]);
        SubjectAssignment::query()->create([
            'class_id' => $class->getKey(),
            'subject_id' => $otherSubject->getKey(),
            'teacher_id' => $otherTeacher->getKey(),
            'minimum_score' => 70,
            'display_order' => 2,
            'active' => true,
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.academic-classes.subject-assignments.index', $class))
            ->assertOk()
            ->assertSee('Matematika')
            ->assertDontSee('Bahasa Indonesia');
        $this->actingAs($teacher)->get(route('admin.academic-classes.subject-assignments.create', $class))->assertForbidden();
    }

    /** @return array{0: Institution, 1: User, 2: AcademicClass, 3: Subject} */
    private function academicContext(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Tahap Lima']);
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
        $subject = Subject::query()->create([
            'institution_id' => $institution->getKey(),
            'code' => 'MAT',
            'name' => 'Matematika',
            'short_name' => 'MTK',
            'active' => true,
        ]);

        return [$institution, $admin, $class, $subject];
    }
}
