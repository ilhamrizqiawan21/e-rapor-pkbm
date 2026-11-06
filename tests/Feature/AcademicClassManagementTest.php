<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Enums\UserStatus;
use App\Http\Controllers\Admin\AcademicClassController;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\ReportCard;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AcademicClassManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::resource('admin/academic-classes', AcademicClassController::class)
                ->parameters(['academic-classes' => 'academic_class'])
                ->names('admin.academic-classes');
        });
    }

    public function test_admin_can_create_a_class_with_a_consistent_program_and_level(): void
    {
        [$admin, $year, $program, $level, $teacher] = $this->academicSetup();

        $response = $this->actingAs($admin)->post(route('admin.academic-classes.store'), [
            'academic_year_id' => $year->id,
            'program_id' => $program->id,
            'level_id' => $level->id,
            'homeroom_teacher_id' => $teacher->id,
            'code' => '  a-1  ',
            'name' => '  Kelas 1 A  ',
            'status' => 'ACTIVE',
        ]);

        $class = AcademicClass::query()->firstOrFail();
        $response->assertRedirect(route('admin.academic-classes.show', $class));
        $this->assertSame('A-1', $class->code);
        $this->assertSame('Kelas 1 A', $class->name);
        $this->assertSame($teacher->id, $class->homeroom_teacher_id);
    }

    public function test_validation_rejects_duplicate_code_and_level_from_another_program(): void
    {
        [$admin, $year, $program, $level] = $this->academicSetup();
        $otherProgram = Program::query()->create(['institution_id' => $program->institution_id, 'code' => 'B', 'name' => 'Paket B']);
        $otherLevel = Level::query()->create(['program_id' => $otherProgram->id, 'number' => 1, 'name' => 'Tingkat 1 Paket B']);
        AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas Lama', 'status' => 'ACTIVE']);

        $this->actingAs($admin)
            ->from(route('admin.academic-classes.create'))
            ->post(route('admin.academic-classes.store'), [
                'academic_year_id' => $year->id,
                'program_id' => $program->id,
                'level_id' => $otherLevel->id,
                'code' => 'a-1',
                'name' => 'Kelas Baru',
                'status' => 'ACTIVE',
            ])
            ->assertRedirect(route('admin.academic-classes.create'))
            ->assertSessionHasErrors(['level_id', 'code']);
    }

    public function test_only_active_guru_from_the_same_institution_can_be_homeroom_teacher(): void
    {
        [$admin, $year, $program, $level] = $this->academicSetup();
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);
        $wrongInstitutionTeacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $otherInstitution->id]);
        $inactiveTeacher = User::factory()->withRole(RoleCode::Guru)->inactive()->create(['institution_id' => $admin->institution_id]);

        foreach ([$wrongInstitutionTeacher, $inactiveTeacher] as $teacher) {
            $this->actingAs($admin)
                ->from(route('admin.academic-classes.create'))
                ->post(route('admin.academic-classes.store'), [
                    'academic_year_id' => $year->id,
                    'program_id' => $program->id,
                    'level_id' => $level->id,
                    'homeroom_teacher_id' => $teacher->id,
                    'code' => 'A-'.$teacher->id,
                    'name' => 'Kelas Validasi',
                    'status' => 'ACTIVE',
                ])
                ->assertSessionHasErrors('homeroom_teacher_id');
        }
    }

    public function test_closed_period_rejects_create_update_and_delete(): void
    {
        [$admin, $year, $program, $level] = $this->academicSetup(['status' => 'CLOSED']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas Tertutup', 'status' => 'ACTIVE']);

        $this->actingAs($admin)->post(route('admin.academic-classes.store'), [
            'academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-2', 'name' => 'Tidak Boleh', 'status' => 'ACTIVE',
        ])->assertStatus(422);
        $this->actingAs($admin)->put(route('admin.academic-classes.update', $class), [
            'academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Tidak Boleh', 'status' => 'ACTIVE',
        ])->assertStatus(422);
        $this->actingAs($admin)->delete(route('admin.academic-classes.destroy', $class))->assertStatus(422);
    }

    public function test_guru_sees_only_assigned_classes_and_kepsek_sees_their_institution(): void
    {
        [$admin, $year, $program, $level, $teacher] = $this->academicSetup();
        $assigned = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'homeroom_teacher_id' => $teacher->id, 'code' => 'A-1', 'name' => 'Kelas Wali', 'status' => 'ACTIVE']);
        AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-2', 'name' => 'Kelas Lain', 'status' => 'ACTIVE']);
        $kepsek = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $admin->institution_id]);

        $this->actingAs($teacher)->get(route('admin.academic-classes.index'))->assertOk()->assertSee('Kelas Wali')->assertDontSee('Kelas Lain');
        $this->actingAs($teacher)->get(route('admin.academic-classes.show', $assigned))->assertOk();
        $this->actingAs($kepsek)->get(route('admin.academic-classes.index'))->assertOk()->assertSee('Kelas Wali')->assertSee('Kelas Lain');
    }

    public function test_detail_shows_students_subjects_and_safe_report_progress(): void
    {
        [$admin, $year, $program, $level, $teacher] = $this->academicSetup();
        Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Semester Ganjil', 'sequence' => 1, 'status' => 'ACTIVE']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'homeroom_teacher_id' => $teacher->id, 'code' => 'A-1', 'name' => 'Kelas Detail', 'status' => 'ACTIVE']);
        $student = Student::query()->create(['institution_id' => $admin->institution_id, 'student_number' => 'S-001', 'full_name' => 'Siswa Detail', 'status' => 'ACTIVE']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => now()->toDateString(), 'enrollment_status' => 'ACTIVE']);
        $subject = Subject::query()->create(['institution_id' => $admin->institution_id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK', 'active' => true]);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
        $reportCard = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $year->semesters()->first()->id, 'status' => 'DRAFT']);
        $reportCard->grades()->create(['subject_assignment_id' => $assignment->id, 'subject_name_snapshot' => 'Matematika', 'subject_short_name_snapshot' => 'MTK', 'minimum_score_snapshot' => 70, 'display_order_snapshot' => 1, 'score' => 85, 'status' => 'DRAFT']);

        $this->actingAs($admin)->get(route('admin.academic-classes.show', $class))->assertOk()->assertSee('Siswa Detail')->assertSee('Matematika')->assertSee('100%')->assertSee('1 dari 1');
    }

    /**
     * @return array{0: User, 1: AcademicYear, 2: Program, 3: Level, 4: User}
     */
    private function academicSetup(array $yearAttributes = []): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Kelas Uji']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id, 'status' => UserStatus::Active]);
        $year = AcademicYear::query()->create(array_merge(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE'], $yearAttributes));
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);

        return [$admin, $year, $program, $level, $teacher];
    }
}
