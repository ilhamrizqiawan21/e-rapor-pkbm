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
use App\Models\User;
use App\Services\ReportCardAssessmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReportCardAssessmentServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_homeroom_teacher_can_save_both_attitude_types_and_attendance(): void
    {
        [$reportCard, $guru] = $this->context();
        $service = app(ReportCardAssessmentService::class);

        $service->saveAttitude($reportCard, $guru, 'spiritual', 'Menunjukkan sikap baik.');
        $service->saveAttitude($reportCard, $guru, 'SOCIAL', 'Bekerja sama dengan teman.');
        $service->saveAttendance($reportCard, $guru, ['sick_days' => 2, 'leave_days' => 3, 'unexcused_days' => 1]);

        $this->assertDatabaseHas('attitude_assessments', ['report_card_id' => $reportCard->id, 'attitude_type' => 'SPIRITUAL', 'description' => 'Menunjukkan sikap baik.']);
        $this->assertDatabaseHas('attitude_assessments', ['report_card_id' => $reportCard->id, 'attitude_type' => 'SOCIAL']);
        $this->assertDatabaseHas('attendance_summaries', ['report_card_id' => $reportCard->id, 'sick_days' => 2, 'leave_days' => 3, 'unexcused_days' => 1]);
    }

    public function test_attendance_rejects_total_over_year(): void
    {
        [$reportCard, $guru] = $this->context();

        $this->expectException(ValidationException::class);
        app(ReportCardAssessmentService::class)->saveAttendance($reportCard, $guru, ['sick_days' => 200, 'leave_days' => 167, 'unexcused_days' => 0]);
    }

    public function test_subject_teacher_cannot_edit_homeroom_assessment(): void
    {
        [$reportCard, , $subjectTeacher] = $this->context(withSubjectTeacher: true);

        $this->expectException(AuthorizationException::class);
        app(ReportCardAssessmentService::class)->saveAttitude($reportCard, $subjectTeacher, 'SOCIAL', 'Tidak boleh.');
    }

    public function test_locked_card_is_read_only(): void
    {
        [$reportCard, $guru] = $this->context(['status' => 'LOCKED']);

        $this->expectException(AuthorizationException::class);
        app(ReportCardAssessmentService::class)->saveAttendance($reportCard, $guru, ['sick_days' => 0, 'leave_days' => 0, 'unexcused_days' => 0]);
    }

    /** @return array{0: ReportCard, 1: User, 2?: User} */
    private function context(array $reportCardAttributes = [], bool $withSubjectTeacher = false): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Sikap Uji']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $subjectTeacher = $withSubjectTeacher ? User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]) : null;
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'homeroom_teacher_id' => $guru->id, 'code' => 'A-1', 'name' => 'Kelas 1', 'status' => 'ACTIVE']);
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-001', 'full_name' => 'Siswa Sikap', 'status' => 'ACTIVE']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1, 'status' => 'ACTIVE']);
        $reportCard = ReportCard::query()->create(array_merge(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'DRAFT'], $reportCardAttributes));

        return $subjectTeacher === null ? [$reportCard, $guru] : [$reportCard, $guru, $subjectTeacher];
    }
}
