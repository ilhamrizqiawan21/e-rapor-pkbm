<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Http\Controllers\Admin\GradeEntryController;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class GradeEntryControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::get('admin/grade-entry', [GradeEntryController::class, 'index'])->name('admin.grade-entry.index');
            Route::post('admin/grade-entry', [GradeEntryController::class, 'store'])->name('admin.grade-entry.store');
            Route::post('admin/grade-entry/context', [GradeEntryController::class, 'storeContext'])->name('admin.grade-entry.store-context');
            Route::post('admin/grade-entry/all', [GradeEntryController::class, 'storeAll'])->name('admin.grade-entry.store-all');
        });
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_guru_can_open_grade_entry_with_context_and_student_grid(): void
    {
        [$guru, $assignment, $semester, $enrollment] = $this->academicSetup();

        $this->actingAs($guru)->get(route('admin.grade-entry.index', [
            'academic_year_id' => $assignment->academicClass->academic_year_id,
            'semester_id' => $semester->id,
            'class_id' => $assignment->class_id,
            'subject_id' => $assignment->subject_id,
        ]))->assertOk()->assertSee('Input nilai')->assertSee('Siswa Uji')->assertSee('KKM 70')->assertSee('Materi / capaian pembelajaran')->assertSee('Deskripsi otomatis')->assertSee('readonly', false)->assertSee('Pilih dari bank deskripsi')->assertDontSee('>Ajukan<', false);
    }

    public function test_single_row_validation_keeps_invalid_score_out_of_database(): void
    {
        [$guru, $assignment, $semester, $enrollment] = $this->academicSetup();
        $payload = $this->context($assignment, $semester, $enrollment) + ['score' => 101, 'description' => 'Invalid'];

        $this->actingAs($guru)->from(route('admin.grade-entry.index'))->post(route('admin.grade-entry.store'), $payload)
            ->assertRedirect()->assertSessionHasErrors('score');
        $this->assertDatabaseCount('report_grades', 0);
    }

    public function test_save_all_reports_row_error_but_saves_valid_rows(): void
    {
        [$guru, $assignment, $semester, $firstEnrollment, $secondEnrollment] = $this->academicSetup(twoStudents: true);
        $payload = $this->context($assignment, $semester, $firstEnrollment) + [
            'rows' => [
                $firstEnrollment->id => ['enrollment_id' => $firstEnrollment->id, 'score' => 80, 'description' => 'Baik.'],
                $secondEnrollment->id => ['enrollment_id' => $secondEnrollment->id, 'score' => 120, 'description' => 'Invalid.'],
            ],
        ];

        $this->actingAs($guru)->post(route('admin.grade-entry.store-all'), $payload)->assertRedirect()->assertSessionHas('grade_errors');
        $this->assertDatabaseHas('report_grades', ['score' => 80, 'description' => 'Baik.']);
        $this->assertDatabaseCount('report_grades', 1);
    }

    public function test_save_all_keeps_shared_material_for_the_next_grade_entry_page(): void
    {
        [$guru, $assignment, $semester, $enrollment] = $this->academicSetup();
        $material = 'memahami pengertian sifat terpuji';
        $payload = $this->context($assignment, $semester, $enrollment) + [
            'material' => $material,
            'rows' => [
                $enrollment->id => ['enrollment_id' => $enrollment->id, 'score' => 90],
            ],
        ];

        $this->actingAs($guru)->post(route('admin.grade-entry.store-all'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success', '1 baris nilai berhasil disimpan.');

        $this->assertDatabaseHas('grade_entry_contexts', [
            'semester_id' => $semester->id,
            'subject_assignment_id' => $assignment->id,
            'material' => $material,
        ]);

        $this->actingAs($guru)->get(route('admin.grade-entry.index', [
            'academic_year_id' => $assignment->academicClass->academic_year_id,
            'semester_id' => $semester->id,
            'class_id' => $assignment->class_id,
            'subject_id' => $assignment->subject_id,
        ]))->assertOk()->assertSee($material);
    }

    public function test_context_can_be_saved_without_mutating_locked_grade_rows(): void
    {
        [$guru, $assignment, $semester, $enrollment] = $this->academicSetup();
        $material = 'memahami pengertian sifat terpuji';
        $context = $this->context($assignment, $semester, $enrollment);
        unset($context['enrollment_id']);

        $this->actingAs($guru)->post(route('admin.grade-entry.store-context'), $context + ['material' => $material])
            ->assertOk()
            ->assertJsonPath('message', 'Materi pembelajaran berhasil disimpan.');

        $this->assertDatabaseHas('grade_entry_contexts', [
            'semester_id' => $semester->id,
            'subject_assignment_id' => $assignment->id,
            'material' => $material,
        ]);
        $this->assertDatabaseCount('report_grades', 0);
    }

    public function test_other_teacher_cannot_open_or_save_this_subject_assignment(): void
    {
        [$guru, $assignment, $semester, $enrollment] = $this->academicSetup();
        $otherGuru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $guru->institution_id]);
        $context = [
            'academic_year_id' => $assignment->academicClass->academic_year_id,
            'semester_id' => $semester->id,
            'class_id' => $assignment->class_id,
            'subject_id' => $assignment->subject_id,
        ];

        $this->actingAs($otherGuru)->get(route('admin.grade-entry.index', $context))->assertOk()->assertDontSee('Matematika');
        $this->actingAs($otherGuru)->post(route('admin.grade-entry.store'), $context + [
            'subject_assignment_id' => $assignment->id,
            'enrollment_id' => $enrollment->id,
            'score' => 80,
        ])->assertForbidden();
    }

    /** @return array<string, int> */
    private function context(SubjectAssignment $assignment, Semester $semester, Enrollment $enrollment): array
    {
        return [
            'academic_year_id' => $assignment->academicClass->academic_year_id,
            'semester_id' => $semester->id,
            'class_id' => $assignment->class_id,
            'subject_id' => $assignment->subject_id,
            'subject_assignment_id' => $assignment->id,
            'enrollment_id' => $enrollment->id,
        ];
    }

    /** @return array<int, mixed> */
    private function academicSetup(bool $twoStudents = false): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Input Nilai Feature']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1, 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas 1 A', 'status' => 'ACTIVE']);
        $subject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK', 'active' => true]);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $guru->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-001', 'full_name' => 'Siswa Uji', 'status' => 'ACTIVE']);
        $firstEnrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);

        if (! $twoStudents) {
            return [$guru, $assignment->load('academicClass'), $semester, $firstEnrollment];
        }

        $secondStudent = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-002', 'full_name' => 'Siswa Kedua', 'status' => 'ACTIVE']);
        $secondEnrollment = Enrollment::query()->create(['student_id' => $secondStudent->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);

        return [$guru, $assignment->load('academicClass'), $semester, $firstEnrollment, $secondEnrollment];
    }
}
