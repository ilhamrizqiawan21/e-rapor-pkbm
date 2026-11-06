<?php

namespace Tests\Feature;

use App\Contracts\PdfEngine;
use App\Enums\RoleCode;
use App\Http\Controllers\Admin\LedgerController;
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
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LedgerControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::get('/__test/ledger/classes/{academicClass}/semesters/{semester}/xlsx', [LedgerController::class, 'classExcel']);
            Route::get('/__test/ledger/classes/{academicClass}/semesters/{semester}/pdf', [LedgerController::class, 'classPdf']);
        });
    }

    public function test_admin_can_download_a_valid_class_ledger_xlsx(): void
    {
        $fixture = $this->fixture();
        $this->actingAs($fixture['admin'])
            ->get('/__test/ledger/classes/'.$fixture['class']->id.'/semesters/'.$fixture['semester']->id.'/xlsx')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition', 'attachment; filename=leger-2025-2026-ganjil-a-1.xlsx');
    }

    public function test_admin_can_open_the_ledger_selector_from_the_application_routes(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['admin'])
            ->get(route('admin.ledger.index'))
            ->assertOk()
            ->assertSee('Generator leger nilai')
            ->assertSee('Unduh semua Excel')
            ->assertSee('Kelas 1');
    }

    public function test_teacher_without_class_permission_is_forbidden(): void
    {
        $fixture = $this->fixture();
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $fixture['institution']->id]);

        $this->actingAs($guru)
            ->get('/__test/ledger/classes/'.$fixture['class']->id.'/semesters/'.$fixture['semester']->id.'/xlsx')
            ->assertForbidden();
    }

    public function test_class_pdf_uses_landscape_ledger_template_contract(): void
    {
        $fixture = $this->fixture();
        app()->instance(PdfEngine::class, new class implements PdfEngine
        {
            public function available(): bool
            {
                return true;
            }

            public function render(string $html): string
            {
                return str_contains($html, 'A4 landscape') ? '%PDF-1.7 ledger' : '%PDF-1.7 invalid';
            }
        });

        $this->actingAs($fixture['admin'])
            ->get('/__test/ledger/classes/'.$fixture['class']->id.'/semesters/'.$fixture['semester']->id.'/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertSee('%PDF-1.7 ledger');
    }

    /** @return array<string, mixed> */
    private function fixture(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Ledger']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2025/2026', 'status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Semester Ganjil', 'sequence' => 1, 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Kelas 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas 1']);
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'NIPD-001', 'full_name' => 'Peserta Ledger']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2025-07-01', 'reporting_enrollment_id' => null]);
        $enrollment->update(['reporting_enrollment_id' => $enrollment->id]);
        $reportCard = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'APPROVED']);
        $subject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MAT']);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $admin->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
        ReportGrade::query()->create(['report_card_id' => $reportCard->id, 'subject_assignment_id' => $assignment->id, 'subject_name_snapshot' => 'Matematika', 'subject_short_name_snapshot' => 'MAT', 'minimum_score_snapshot' => 70, 'display_order_snapshot' => 1, 'score' => 80, 'predicate' => 'B', 'description' => 'Baik.', 'status' => 'DRAFT']);

        return compact('institution', 'admin', 'year', 'semester', 'program', 'level', 'class', 'student', 'enrollment', 'reportCard');
    }
}
