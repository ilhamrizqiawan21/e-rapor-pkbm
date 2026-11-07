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
use App\Models\ReportGrade;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GradeMonitoringControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_kepsek_can_monitor_grade_progress_without_write_controls(): void
    {
        [$institution, $semester, $academicClass, $assignment] = $this->academicSetup();
        $kepsek = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $institution->id]);
        $enrollment = $this->enrollment($academicClass, $institution, 'PD-001', 'Siswa Monitoring');
        $reportCard = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'DRAFT']);
        ReportGrade::query()->create([
            'report_card_id' => $reportCard->id,
            'subject_assignment_id' => $assignment->id,
            'subject_name_snapshot' => 'Matematika',
            'subject_short_name_snapshot' => 'MTK',
            'minimum_score_snapshot' => 70,
            'display_order_snapshot' => 1,
            'score' => 88,
            'predicate' => 'B',
            'description' => 'Capaian baik.',
            'status' => 'DRAFT',
        ]);

        $this->actingAs($kepsek)->get(route('admin.grade-monitoring.index', ['semester_id' => $semester->id]))
            ->assertOk()
            ->assertSee('Progres nilai')
            ->assertSee('Mode hanya baca')
            ->assertSee('Matematika')
            ->assertSee('100%')
            ->assertDontSee('Simpan nilai');
    }

    public function test_guru_cannot_open_grade_monitoring(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Monitoring']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);

        $this->actingAs($guru)->get(route('admin.grade-monitoring.index'))->assertForbidden();
    }

    /** @return array{0: Institution, 1: Semester, 2: AcademicClass, 3: SubjectAssignment} */
    private function academicSetup(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Monitoring']);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1, 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas 1 A', 'status' => 'ACTIVE']);
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $subject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK', 'active' => true]);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);

        return [$institution, $semester, $class, $assignment];
    }

    private function enrollment(AcademicClass $class, Institution $institution, string $number, string $name): Enrollment
    {
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => $number, 'full_name' => $name, 'status' => 'ACTIVE']);

        return Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $class->academic_year_id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);
    }
}
