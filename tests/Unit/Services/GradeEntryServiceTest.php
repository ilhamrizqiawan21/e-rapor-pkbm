<?php

namespace Tests\Unit\Services;

use App\Enums\RoleCode;
use App\Exceptions\GradeConcurrencyConflict;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\DescriptionSnippet;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\ReportGrade;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\GradeEntryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GradeEntryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_report_card_and_snapshots_grade_and_description(): void
    {
        [$guru, $assignment, $semester, $enrollment] = $this->academicSetup();
        $snippet = DescriptionSnippet::query()->create([
            'institution_id' => $guru->institution_id,
            'owner_user_id' => $guru->id,
            'subject_id' => $assignment->subject_id,
            'title' => 'Capaian membaca',
            'content' => 'Mampu membaca teks dengan lancar.',
            'visibility' => 'PRIVATE',
            'active' => true,
        ]);

        $grade = app(GradeEntryService::class)->saveRow($guru, $this->row($assignment, $semester, $enrollment, [
            'score' => 88,
            'description_snippet_id' => $snippet->id,
        ]));

        $this->assertSame(88, $grade->score);
        $this->assertSame('A', $grade->predicate);
        $this->assertSame($snippet->id, $grade->description_snippet_id);
        $this->assertSame('Mampu membaca teks dengan lancar.', $grade->description);
        $this->assertDatabaseHas('report_cards', ['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id]);
    }

    public function test_it_rejects_invalid_scores_without_creating_a_grade(): void
    {
        [$guru, $assignment, $semester, $enrollment] = $this->academicSetup();

        $this->expectException(ValidationException::class);
        app(GradeEntryService::class)->saveRow($guru, $this->row($assignment, $semester, $enrollment, ['score' => 101]));

        $this->assertDatabaseCount('report_cards', 0);
        $this->assertDatabaseCount('report_grades', 0);
    }

    public function test_save_all_keeps_valid_rows_when_another_row_is_invalid(): void
    {
        [$guru, $assignment, $semester, $firstEnrollment, $secondEnrollment] = $this->academicSetup(twoStudents: true);

        $result = app(GradeEntryService::class)->saveRows($guru, [
            'academic_year_id' => $assignment->academicClass->academic_year_id,
            'semester_id' => $semester->id,
            'class_id' => $assignment->class_id,
            'subject_id' => $assignment->subject_id,
            'subject_assignment_id' => $assignment->id,
        ], [
            $this->row($assignment, $semester, $firstEnrollment, ['score' => 75]),
            $this->row($assignment, $semester, $secondEnrollment, ['score' => 101]),
        ]);

        $this->assertSame(1, $result['saved']);
        $this->assertArrayHasKey($secondEnrollment->id, $result['errors']);
        $this->assertDatabaseHas('report_grades', ['score' => 75]);
        $this->assertDatabaseCount('report_grades', 1);
    }

    public function test_repeated_save_updates_the_same_unique_grade(): void
    {
        [$guru, $assignment, $semester, $enrollment] = $this->academicSetup();
        $service = app(GradeEntryService::class);
        $service->saveRow($guru, $this->row($assignment, $semester, $enrollment, ['score' => 70]));
        $updated = $service->saveRow($guru, $this->row($assignment, $semester, $enrollment, ['score' => 92]));

        $this->assertSame(92, $updated->score);
        $this->assertDatabaseCount('report_grades', 1);
    }

    public function test_stale_updated_at_cannot_overwrite_a_newer_grade(): void
    {
        [$guru, $assignment, $semester, $enrollment] = $this->academicSetup();
        $service = app(GradeEntryService::class);
        $grade = $service->saveRow($guru, $this->row($assignment, $semester, $enrollment, ['score' => 70]));
        $staleUpdatedAt = $grade->updated_at->toIso8601String();

        ReportGrade::query()->whereKey($grade->id)->update([
            'score' => 88,
            'updated_at' => now()->addMinute(),
        ]);

        try {
            $service->saveRow($guru, $this->row($assignment, $semester, $enrollment, [
                'score' => 60,
                'expected_updated_at' => $staleUpdatedAt,
            ]));
            $this->fail('Save dengan timestamp lama seharusnya ditolak.');
        } catch (GradeConcurrencyConflict $exception) {
            $this->assertSame(88, $exception->grade()->score);
        }

        $this->assertSame(88, $grade->refresh()->score);
    }

    public function test_teacher_cannot_save_a_subject_assignment_owned_by_another_teacher(): void
    {
        [$guru, $assignment, $semester, $enrollment] = $this->academicSetup();
        $otherGuru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $guru->institution_id]);

        $this->expectException(AuthorizationException::class);
        app(GradeEntryService::class)->saveRow($otherGuru, $this->row($assignment, $semester, $enrollment, ['score' => 80]));
    }

    /** @return array<string, mixed> */
    private function row(SubjectAssignment $assignment, Semester $semester, Enrollment $enrollment, array $overrides = []): array
    {
        return array_merge([
            'academic_year_id' => $assignment->academicClass->academic_year_id,
            'semester_id' => $semester->id,
            'class_id' => $assignment->class_id,
            'subject_id' => $assignment->subject_id,
            'subject_assignment_id' => $assignment->id,
            'enrollment_id' => $enrollment->id,
            'description' => null,
        ], $overrides);
    }

    /** @return array<int, mixed> */
    private function academicSetup(bool $twoStudents = false): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Input Nilai Uji']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1, 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas 1 A', 'status' => 'ACTIVE']);
        $subject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK', 'active' => true]);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $guru->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
        $firstStudent = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-001', 'full_name' => 'Siswa Pertama', 'status' => 'ACTIVE']);
        $firstEnrollment = Enrollment::query()->create(['student_id' => $firstStudent->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);

        if (! $twoStudents) {
            return [$guru, $assignment->load('academicClass'), $semester, $firstEnrollment];
        }

        $secondStudent = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-002', 'full_name' => 'Siswa Kedua', 'status' => 'ACTIVE']);
        $secondEnrollment = Enrollment::query()->create(['student_id' => $secondStudent->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);

        return [$guru, $assignment->load('academicClass'), $semester, $firstEnrollment, $secondEnrollment];
    }
}
