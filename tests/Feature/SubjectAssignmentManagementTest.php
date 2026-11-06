<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Http\Controllers\Admin\SubjectAssignmentController;
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

class SubjectAssignmentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::resource('admin/academic-classes.subject-assignments', SubjectAssignmentController::class)
                ->except(['show'])
                ->parameters([
                    'academic-classes' => 'academic_class',
                    'subject-assignments' => 'subject_assignment',
                ])
                ->names('admin.academic-classes.subject-assignments');
        });
    }

    public function test_admin_can_create_update_and_delete_assignment(): void
    {
        [$admin, $guru, $class, $subject] = $this->academicSetup();

        $this->actingAs($admin)->post(route('admin.academic-classes.subject-assignments.store', $class), [
            'subject_id' => $subject->id,
            'teacher_id' => $guru->id,
            'minimum_score' => 75,
            'display_order' => 2,
            'active' => 1,
        ])->assertRedirect(route('admin.academic-classes.subject-assignments.index', $class));

        $assignment = SubjectAssignment::query()->firstOrFail();
        $this->assertSame(75, $assignment->minimum_score);

        $this->actingAs($admin)->put(route('admin.academic-classes.subject-assignments.update', [$class, $assignment]), [
            'subject_id' => $subject->id,
            'teacher_id' => $guru->id,
            'minimum_score' => 80,
            'display_order' => 1,
            'active' => 0,
        ])->assertRedirect(route('admin.academic-classes.subject-assignments.index', $class));

        $this->assertDatabaseHas('subject_assignments', ['id' => $assignment->id, 'minimum_score' => 80, 'active' => false]);
        $this->actingAs($admin)->delete(route('admin.academic-classes.subject-assignments.destroy', [$class, $assignment]))->assertRedirect();
        $this->assertDatabaseMissing('subject_assignments', ['id' => $assignment->id]);
    }

    public function test_validation_rejects_duplicate_cross_institution_and_non_guru_teacher(): void
    {
        [$admin, $guru, $class, $subject] = $this->academicSetup();
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);
        $otherSubject = Subject::query()->create(['institution_id' => $otherInstitution->id, 'code' => 'MAT', 'name' => 'Matematika Lain', 'short_name' => 'MTK']);
        $wrongTeacher = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $admin->institution_id]);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $guru->id]);

        $this->actingAs($admin)->from(route('admin.academic-classes.subject-assignments.create', $class))
            ->post(route('admin.academic-classes.subject-assignments.store', $class), [
                'subject_id' => $subject->id, 'teacher_id' => $wrongTeacher->id, 'minimum_score' => 101, 'display_order' => 0, 'active' => 1,
            ])->assertRedirect()->assertSessionHasErrors(['subject_id', 'teacher_id', 'minimum_score', 'display_order']);

        $this->actingAs($admin)->post(route('admin.academic-classes.subject-assignments.store', $class), [
            'subject_id' => $otherSubject->id, 'teacher_id' => $guru->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => 1,
        ])->assertSessionHasErrors('subject_id');

        $this->assertDatabaseHas('subject_assignments', ['id' => $assignment->id]);
    }

    public function test_guru_only_sees_own_assignments_and_kepsek_sees_all(): void
    {
        [$admin, $guru, $class, $subject] = $this->academicSetup();
        $otherGuru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $admin->institution_id]);
        $otherSubject = Subject::query()->create(['institution_id' => $admin->institution_id, 'code' => 'BIN', 'name' => 'Bahasa Indonesia', 'short_name' => 'BIN']);
        SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $guru->id]);
        SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $otherSubject->id, 'teacher_id' => $otherGuru->id]);
        $kepsek = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $admin->institution_id]);

        $this->actingAs($guru)->get(route('admin.academic-classes.subject-assignments.index', $class))->assertOk()->assertSee('Matematika')->assertDontSee('Bahasa Indonesia');
        $this->actingAs($kepsek)->get(route('admin.academic-classes.subject-assignments.index', $class))->assertOk()->assertSee('Matematika')->assertSee('Bahasa Indonesia');
    }

    public function test_closed_period_rejects_assignment_changes(): void
    {
        [$admin, $guru, $class, $subject] = $this->academicSetup(['status' => 'CLOSED']);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $guru->id]);

        $this->actingAs($admin)->post(route('admin.academic-classes.subject-assignments.store', $class), [
            'subject_id' => $subject->id, 'teacher_id' => $guru->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => 1,
        ])->assertStatus(422);
        $this->actingAs($admin)->put(route('admin.academic-classes.subject-assignments.update', [$class, $assignment]), [
            'subject_id' => $subject->id, 'teacher_id' => $guru->id, 'minimum_score' => 71, 'display_order' => 1, 'active' => 1,
        ])->assertStatus(422);
        $this->actingAs($admin)->delete(route('admin.academic-classes.subject-assignments.destroy', [$class, $assignment]))->assertStatus(422);
    }

    public function test_locked_report_grade_rejects_update_and_delete(): void
    {
        [$admin, $guru, $class, $subject, $year] = $this->academicSetup();
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $guru->id]);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1]);
        $student = Student::query()->create(['institution_id' => $admin->institution_id, 'student_number' => 'PD-001', 'full_name' => 'Siswa Uji']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01']);
        $reportCard = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'LOCKED']);
        $reportCard->grades()->create(['subject_assignment_id' => $assignment->id, 'subject_name_snapshot' => 'Matematika', 'subject_short_name_snapshot' => 'MTK', 'minimum_score_snapshot' => 70, 'display_order_snapshot' => 1, 'score' => 80, 'status' => 'LOCKED']);

        $this->actingAs($admin)->put(route('admin.academic-classes.subject-assignments.update', [$class, $assignment]), [
            'subject_id' => $subject->id, 'teacher_id' => $guru->id, 'minimum_score' => 71, 'display_order' => 1, 'active' => 0,
        ])->assertStatus(422);
        $this->actingAs($admin)->delete(route('admin.academic-classes.subject-assignments.destroy', [$class, $assignment]))->assertStatus(422);
        $this->assertDatabaseHas('subject_assignments', ['id' => $assignment->id, 'active' => true]);
    }

    public function test_create_view_has_a_clear_empty_state_without_subjects(): void
    {
        [$admin, , $class] = $this->academicSetup();
        Subject::query()->delete();

        $this->actingAs($admin)->get(route('admin.academic-classes.subject-assignments.create', $class))
            ->assertOk()
            ->assertSee('Belum ada mata pelajaran aktif');
    }

    /**
     * @return array{0: User, 1: User, 2: AcademicClass, 3: Subject, 4: AcademicYear}
     */
    private function academicSetup(array $yearAttributes = []): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Penugasan Uji']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(array_merge(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE'], $yearAttributes));
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas 1 A', 'status' => 'ACTIVE']);
        $subject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK']);

        return [$admin, $guru, $class, $subject, $year];
    }
}
