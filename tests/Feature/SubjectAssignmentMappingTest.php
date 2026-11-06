<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
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
use App\Services\SubjectAssignmentMappingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubjectAssignmentMappingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_bulk_add_mapping_without_touching_subject_master(): void
    {
        [$admin, $teacher, , $classes, $subjects] = $this->academicSetup();

        $existing = SubjectAssignment::query()->create([
            'class_id' => $classes[0]->id,
            'subject_id' => $subjects[0]->id,
            'teacher_id' => $teacher->id,
            'minimum_score' => 72,
            'display_order' => 1,
        ]);

        $result = $this->service()->bulkAdd([$classes[0]->id, $classes[1]->id], [$subjects[0]->id, $subjects[1]->id], $teacher->id, 78, $admin);

        $this->assertSame(['created' => 3, 'skipped' => 1, 'classes' => 2, 'subjects' => 2], $result);
        $this->assertDatabaseCount('subjects', 2);
        $this->assertDatabaseHas('subject_assignments', ['id' => $existing->id, 'minimum_score' => 72]);
        $this->assertDatabaseCount('subject_assignments', 4);
        $this->assertDatabaseHas('subject_assignments', ['class_id' => $classes[1]->id, 'subject_id' => $subjects[1]->id, 'minimum_score' => 78]);
    }

    public function test_copy_configuration_upserts_target_without_deleting_extra_mapping(): void
    {
        [$admin, $teacher, $secondTeacher, $classes, $subjects] = $this->academicSetup();
        $sourceFirst = SubjectAssignment::query()->create(['class_id' => $classes[0]->id, 'subject_id' => $subjects[0]->id, 'teacher_id' => $teacher->id, 'minimum_score' => 80, 'display_order' => 2]);
        SubjectAssignment::query()->create(['class_id' => $classes[0]->id, 'subject_id' => $subjects[1]->id, 'teacher_id' => $secondTeacher->id, 'minimum_score' => 75, 'display_order' => 1, 'active' => false]);
        $targetExisting = SubjectAssignment::query()->create(['class_id' => $classes[1]->id, 'subject_id' => $subjects[0]->id, 'teacher_id' => $secondTeacher->id, 'minimum_score' => 60, 'display_order' => 1]);
        $extraSubject = Subject::query()->create(['institution_id' => $admin->institution_id, 'code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam', 'short_name' => 'IPA']);
        SubjectAssignment::query()->create(['class_id' => $classes[1]->id, 'subject_id' => $extraSubject->id, 'teacher_id' => $teacher->id, 'minimum_score' => 70, 'display_order' => 4]);

        $result = $this->service()->copyConfiguration($classes[0], $classes[1], $admin);

        $this->assertSame(['created' => 1, 'updated' => 1, 'skipped' => 0], $result);
        $this->assertDatabaseHas('subject_assignments', ['id' => $targetExisting->id, 'teacher_id' => $teacher->id, 'minimum_score' => 80, 'display_order' => 2]);
        $this->assertDatabaseHas('subject_assignments', ['class_id' => $classes[1]->id, 'subject_id' => $subjects[1]->id, 'teacher_id' => $secondTeacher->id, 'minimum_score' => 75, 'display_order' => 1, 'active' => false]);
        $this->assertDatabaseHas('subject_assignments', ['class_id' => $classes[1]->id, 'subject_id' => $extraSubject->id]);
        $this->assertDatabaseHas('subject_assignments', ['id' => $sourceFirst->id, 'minimum_score' => 80, 'display_order' => 2]);
    }

    public function test_reorder_and_bulk_teacher_assignment_are_transactional_operations(): void
    {
        [$admin, $teacher, $secondTeacher, $classes, $subjects] = $this->academicSetup();
        $first = SubjectAssignment::query()->create(['class_id' => $classes[0]->id, 'subject_id' => $subjects[0]->id, 'teacher_id' => $teacher->id, 'minimum_score' => 70, 'display_order' => 1]);
        $second = SubjectAssignment::query()->create(['class_id' => $classes[0]->id, 'subject_id' => $subjects[1]->id, 'teacher_id' => $teacher->id, 'minimum_score' => 71, 'display_order' => 2]);

        $this->service()->reorder($classes[0], [$second->id, $first->id], $admin);
        $this->assertDatabaseHas('subject_assignments', ['id' => $second->id, 'display_order' => 1]);
        $this->assertDatabaseHas('subject_assignments', ['id' => $first->id, 'display_order' => 2]);

        $this->assertSame(2, $this->service()->bulkAssignTeacher([$first->id, $second->id], $secondTeacher->id, $admin));
        $this->assertDatabaseHas('subject_assignments', ['id' => $first->id, 'teacher_id' => $secondTeacher->id]);
        $this->assertDatabaseHas('subject_assignments', ['id' => $second->id, 'teacher_id' => $secondTeacher->id]);
    }

    public function test_locked_report_and_closed_period_block_mapping_changes(): void
    {
        [$admin, $teacher, , $classes, $subjects, $year] = $this->academicSetup();
        $assignment = SubjectAssignment::query()->create(['class_id' => $classes[0]->id, 'subject_id' => $subjects[0]->id, 'teacher_id' => $teacher->id, 'minimum_score' => 70, 'display_order' => 1]);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1]);
        $student = Student::query()->create(['institution_id' => $admin->institution_id, 'student_number' => 'PD-MAP-1', 'full_name' => 'Siswa Mapping']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $classes[0]->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01']);
        $report = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'LOCKED']);
        $report->grades()->create(['subject_assignment_id' => $assignment->id, 'subject_name_snapshot' => 'Matematika', 'subject_short_name_snapshot' => 'MAT', 'minimum_score_snapshot' => 70, 'display_order_snapshot' => 1, 'score' => 80, 'status' => 'LOCKED']);

        $this->assertMappingFails(fn () => $this->service()->bulkAssignTeacher([$assignment->id], $teacher->id, $admin));
        $this->assertMappingFails(fn () => $this->service()->reorder($classes[0], [$assignment->id], $admin));

        $year->update(['status' => 'CLOSED']);
        $this->assertMappingFails(fn () => $this->service()->bulkAdd([$classes[1]->id], [$subjects[0]->id], $teacher->id, 70, $admin));
    }

    public function test_non_admin_cannot_use_mapping_operations(): void
    {
        [, $teacher, , $classes, $subjects] = $this->academicSetup();

        $this->assertMappingFails(fn () => $this->service()->bulkAdd([$classes[0]->id], [$subjects[0]->id], $teacher->id, 70, $teacher));
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3: array<int, AcademicClass>, 4: array<int, Subject>, 5: AcademicYear}
     */
    private function academicSetup(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Mapping Uji']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $secondTeacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $classes = [
            AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1A', 'name' => 'Kelas 1 A', 'status' => 'ACTIVE']),
            AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1B', 'name' => 'Kelas 1 B', 'status' => 'ACTIVE']),
        ];
        $subjects = [
            Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MAT']),
            Subject::query()->create(['institution_id' => $institution->id, 'code' => 'BIN', 'name' => 'Bahasa Indonesia', 'short_name' => 'BIN']),
        ];

        return [$admin, $teacher, $secondTeacher, $classes, $subjects, $year];
    }

    private function service(): SubjectAssignmentMappingService
    {
        return app(SubjectAssignmentMappingService::class);
    }

    private function assertMappingFails(\Closure $operation): void
    {
        try {
            $operation();
            $this->fail('Mapping operation seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
    }
}
