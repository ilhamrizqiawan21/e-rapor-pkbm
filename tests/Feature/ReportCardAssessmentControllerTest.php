<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Http\Controllers\Admin\ReportCardAssessmentController;
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

class ReportCardAssessmentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware(['web', 'auth'])->prefix('admin/report-cards/{report_card}/assessments')->group(function (): void {
            Route::get('/', [ReportCardAssessmentController::class, 'show'])->name('admin.report-cards.assessments.show');
            Route::post('/attitude', [ReportCardAssessmentController::class, 'updateAttitude'])->name('admin.report-cards.assessments.attitude');
            Route::post('/attendance', [ReportCardAssessmentController::class, 'updateAttendance'])->name('admin.report-cards.assessments.attendance');
        });
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_homeroom_teacher_can_open_surface_and_save_attitude_and_attendance(): void
    {
        [$reportCard, $guru] = $this->context();

        $this->actingAs($guru)->get(route('admin.report-cards.assessments.show', $reportCard))->assertOk()->assertSee('Sikap dan absensi')->assertSee('Sikap spiritual')->assertSee('Pilih dari bank deskripsi');
        $this->actingAs($guru)->post(route('admin.report-cards.assessments.attitude', $reportCard), ['attitude_type' => 'SPIRITUAL', 'description' => 'Rajin beribadah.'])->assertRedirect()->assertSessionHas('status');
        $this->actingAs($guru)->post(route('admin.report-cards.assessments.attendance', $reportCard), ['sick_days' => 1, 'leave_days' => 2, 'unexcused_days' => 0])->assertRedirect();

        $this->assertDatabaseHas('attitude_assessments', ['report_card_id' => $reportCard->id, 'attitude_type' => 'SPIRITUAL', 'description' => 'Rajin beribadah.']);
        $this->assertDatabaseHas('attendance_summaries', ['report_card_id' => $reportCard->id, 'sick_days' => 1, 'leave_days' => 2]);
    }

    public function test_invalid_attendance_and_non_homeroom_teacher_are_rejected(): void
    {
        [$reportCard, $guru] = $this->context();
        $otherGuru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $guru->institution_id]);
        $subject = Subject::query()->create([
            'institution_id' => $guru->institution_id,
            'code' => 'ASSESSMENT-OTHER',
            'name' => 'Mapel Guru Lain',
            'short_name' => 'MGL',
        ]);
        SubjectAssignment::query()->create([
            'class_id' => $reportCard->enrollment->class_id,
            'subject_id' => $subject->id,
            'teacher_id' => $otherGuru->id,
        ]);

        $this->actingAs($guru)->post(route('admin.report-cards.assessments.attendance', $reportCard), ['sick_days' => 300, 'leave_days' => 67, 'unexcused_days' => 1])->assertSessionHasErrors('attendance');
        $this->actingAs($otherGuru)->get(route('admin.report-cards.assessments.show', $reportCard))->assertForbidden();
        $this->actingAs($otherGuru)->post(route('admin.report-cards.assessments.attitude', $reportCard), ['attitude_type' => 'SOCIAL', 'description' => 'Tidak boleh.'])->assertForbidden();
    }

    public function test_locked_report_card_cannot_be_changed(): void
    {
        [$reportCard, $guru] = $this->context(['status' => 'LOCKED']);

        $this->actingAs($guru)->post(route('admin.report-cards.assessments.attendance', $reportCard), ['sick_days' => 0, 'leave_days' => 0, 'unexcused_days' => 0])->assertForbidden();
    }

    /** @return array{0: ReportCard, 1: User} */
    private function context(array $reportCardAttributes = []): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Assessment Feature']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'homeroom_teacher_id' => $guru->id, 'code' => 'A-1', 'name' => 'Kelas 1', 'status' => 'ACTIVE']);
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-001', 'full_name' => 'Siswa Assessment', 'status' => 'ACTIVE']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $class->academic_year_id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1, 'status' => 'ACTIVE']);
        $reportCard = ReportCard::query()->create(array_merge(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'DRAFT'], $reportCardAttributes));

        return [$reportCard, $guru];
    }
}
