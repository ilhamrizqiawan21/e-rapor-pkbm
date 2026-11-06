<?php

namespace Tests\Unit\Services;

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
use App\Services\KkmAssignmentService;
use App\Services\PredicateRangeService;
use App\Services\ReportGradeSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class KkmAndReportGradeSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_snapshots_kkm_and_predicate_rules_when_grade_is_created(): void
    {
        [$assignment, $reportCard] = $this->academicSetup();
        $grade = app(ReportGradeSnapshotService::class)->createSnapshot($reportCard, $assignment, 80, 'Baik dan konsisten.');

        $this->assertSame(70, $grade->minimum_score_snapshot);
        $this->assertSame('B', $grade->predicate);
        $this->assertSame('B', $grade->predicate_snapshot);
        $this->assertCount(4, $grade->predicate_ranges_snapshot);
        $this->assertSame('Baik dan konsisten.', $grade->description);
    }

    public function test_editable_score_uses_the_snapshotted_predicate_rules(): void
    {
        [$assignment, $reportCard] = $this->academicSetup();
        $grade = app(ReportGradeSnapshotService::class)->createSnapshot($reportCard, $assignment, 80);
        $assignment->update(['minimum_score' => 90]);

        $grade = app(ReportGradeSnapshotService::class)->updateScore($grade, 60);

        $this->assertSame(60, $grade->score);
        $this->assertSame('C', $grade->predicate);
        $this->assertSame(70, $grade->minimum_score_snapshot);
    }

    public function test_locked_report_grades_are_immutable(): void
    {
        [$assignment, $reportCard] = $this->academicSetup();
        $grade = app(ReportGradeSnapshotService::class)->createSnapshot($reportCard, $assignment, 80);
        $reportCard->update(['status' => 'LOCKED', 'locked_at' => now()]);

        try {
            app(ReportGradeSnapshotService::class)->updateScore($grade, 90);
            $this->fail('Service seharusnya menolak perubahan nilai terkunci.');
        } catch (LogicException) {
            $this->assertSame(80, $grade->fresh()->score);
        }

        $this->expectException(LogicException::class);
        $grade->update(['score' => 91]);
    }

    public function test_kkm_service_rejects_invalid_values_and_locked_changes(): void
    {
        [$assignment, $reportCard] = $this->academicSetup();
        $service = app(KkmAssignmentService::class);

        try {
            $service->assign($assignment, 101);
            $this->fail('KKM di luar rentang seharusnya ditolak.');
        } catch (ValidationException) {
            $this->assertSame(70, $assignment->fresh()->minimum_score);
        }

        app(ReportGradeSnapshotService::class)->createSnapshot($reportCard, $assignment, 80);
        $reportCard->update(['status' => 'LOCKED']);

        $this->expectException(LogicException::class);
        $service->assign($assignment, 75);
    }

    /** @return array{0: SubjectAssignment, 1: ReportCard} */
    private function academicSetup(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Snapshot Uji']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1]);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas 1 A', 'status' => 'ACTIVE']);
        $subject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK']);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $guru->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-001', 'full_name' => 'Siswa Uji']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);
        $reportCard = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'DRAFT']);

        app(PredicateRangeService::class)->ensureDefaults($institution);

        return [$assignment, $reportCard];
    }
}
