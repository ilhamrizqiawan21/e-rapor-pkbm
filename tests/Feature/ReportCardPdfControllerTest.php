<?php

namespace Tests\Feature;

use App\Contracts\PdfEngine;
use App\Enums\RoleCode;
use App\Http\Controllers\Admin\ReportCardPdfController;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\AttendanceSummary;
use App\Models\AttitudeAssessment;
use App\Models\Enrollment;
use App\Models\HomeroomNote;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\ReportCard;
use App\Models\ReportExtracurricular;
use App\Models\ReportGrade;
use App\Models\ReportSignature;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\Reports\ChromePdfEngine;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportCardPdfControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::get('/__test/report-cards/{report_card}/pdf/preview', [ReportCardPdfController::class, 'preview']);
            Route::get('/__test/report-cards/{report_card}/pdf/download', [ReportCardPdfController::class, 'download']);
            Route::get('/__test/report-cards/{report_card}/pdf/print', [ReportCardPdfController::class, 'print']);
            Route::get('/__test/classes/{academicClass}/semesters/{semester}/pdf', [ReportCardPdfController::class, 'batch']);
        });
    }

    public function test_preview_uses_locked_identity_and_signature_snapshots(): void
    {
        $fixture = $this->fixture('LOCKED');
        $fixture['student']->update(['full_name' => 'Nama Master Terbaru']);
        $fixture['institution']->update(['name' => 'PKBM Master Terbaru']);

        $this->actingAs($fixture['admin'])
            ->get('/__test/report-cards/'.$fixture['reportCard']->id.'/pdf/preview')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('Siti Snapshot')
            ->assertSee('PKBM Snapshot')
            ->assertSee('Kepala Snapshot')
            ->assertDontSee('Nama Master Terbaru')
            ->assertDontSee('PKBM Master Terbaru');
    }

    public function test_download_returns_pdf_content_type_and_deterministic_filename(): void
    {
        $fixture = $this->fixture('APPROVED');
        app()->instance(PdfEngine::class, new FakePdfEngine);

        $response = $this->actingAs($fixture['admin'])
            ->get('/__test/report-cards/'.$fixture['reportCard']->id.'/pdf/download');

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="rapor-2025-2026-ganjil-nipd-001-'.$fixture['reportCard']->id.'.pdf"');
        $this->assertSame('%PDF-1.7 fake', $response->getContent());
    }

    public function test_unavailable_pdf_engine_returns_graceful_service_unavailable(): void
    {
        $fixture = $this->fixture('APPROVED');
        app()->instance(PdfEngine::class, new ChromePdfEngine('/definitely/not/a/browser'));

        $this->actingAs($fixture['admin'])
            ->get('/__test/report-cards/'.$fixture['reportCard']->id.'/pdf/download')
            ->assertStatus(503)
            ->assertSee('Mesin PDF belum tersedia');
    }

    public function test_batch_returns_zip_with_deterministic_report_filename(): void
    {
        $fixture = $this->fixture('APPROVED');
        app()->instance(PdfEngine::class, new FakePdfEngine);

        $response = $this->actingAs($fixture['admin'])
            ->get('/__test/classes/'.$fixture['class']->id.'/semesters/'.$fixture['semester']->id.'/pdf');

        $response->assertOk()->assertHeader('Content-Type', 'application/zip');
        $archivePath = $response->getFile()->getPathname();
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($archivePath) === true);
        $this->assertSame(
            'rapor-2025-2026-ganjil-nipd-001-'.$fixture['reportCard']->id.'.pdf',
            $zip->getNameIndex(0),
        );
        $zip->close();
    }

    public function test_guru_cannot_batch_export_a_class_without_assignment_access(): void
    {
        $fixture = $this->fixture('APPROVED');
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $fixture['institution']->id]);

        $this->actingAs($guru)
            ->get('/__test/classes/'.$fixture['class']->id.'/semesters/'.$fixture['semester']->id.'/pdf')
            ->assertForbidden();
    }

    /** @return array<string, mixed> */
    private function fixture(string $status): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Snapshot']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2025/2026', 'status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Semester Ganjil', 'sequence' => 1, 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Kelas 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas 1']);
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'NIPD-001', 'national_student_number' => '1234567890', 'full_name' => 'Siti Master']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2025-07-01']);
        $reportCard = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'document_number' => 'RAPOR-001', 'status' => $status]);
        $subject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'Mat']);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $admin->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
        ReportGrade::query()->create(['report_card_id' => $reportCard->id, 'subject_assignment_id' => $assignment->id, 'subject_name_snapshot' => 'Matematika', 'subject_short_name_snapshot' => 'Mat', 'minimum_score_snapshot' => 70, 'display_order_snapshot' => 1, 'score' => 88, 'predicate' => 'B', 'description' => 'Menguasai operasi bilangan dengan baik.', 'status' => 'DRAFT']);
        AttitudeAssessment::query()->create(['report_card_id' => $reportCard->id, 'attitude_type' => 'SPIRITUAL', 'description' => 'Rajin beribadah.']);
        AttitudeAssessment::query()->create(['report_card_id' => $reportCard->id, 'attitude_type' => 'SOCIAL', 'description' => 'Bekerja sama dengan baik.']);
        AttendanceSummary::query()->create(['report_card_id' => $reportCard->id, 'sick_days' => 1, 'leave_days' => 2, 'unexcused_days' => 0]);
        ReportExtracurricular::query()->create(['report_card_id' => $reportCard->id, 'activity_name' => 'Pramuka', 'description' => 'Aktif.', 'display_order' => 1]);
        HomeroomNote::query()->create(['report_card_id' => $reportCard->id, 'note' => 'Pertahankan semangat belajar.']);

        if ($status === 'LOCKED') {
            $reportCard->forceFill([
                'identity_snapshot' => [
                    'student' => ['name' => 'Siti Snapshot', 'student_number' => 'NIPD-SNAPSHOT', 'nisn' => '9999999999'],
                    'class' => ['name' => 'Kelas Snapshot', 'code' => 'A-S', 'level' => 'Kelas 1', 'program' => 'Paket A'],
                    'semester' => ['code' => 'GANJIL', 'name' => 'Semester Snapshot'],
                    'academic_year' => ['name' => '2025/2026'],
                    'institution' => ['name' => 'PKBM Snapshot', 'address' => 'Alamat Snapshot'],
                    'document_number' => 'RAPOR-001',
                ],
                'locked_at' => now(),
            ])->saveQuietly();
            ReportSignature::query()->create(['report_card_id' => $reportCard->id, 'signer_type' => 'HEAD_OF_PKBM', 'signer_name_snapshot' => 'Kepala Snapshot', 'signer_title_snapshot' => 'Kepala PKBM', 'signed_at' => now()]);
            ReportSignature::query()->create(['report_card_id' => $reportCard->id, 'signer_type' => 'HOMEROOM_TEACHER', 'signer_name_snapshot' => 'Wali Snapshot', 'signer_title_snapshot' => 'Wali Kelas', 'signed_at' => now()]);
        }

        return compact('institution', 'admin', 'year', 'semester', 'program', 'level', 'class', 'student', 'enrollment', 'reportCard');
    }
}

final class FakePdfEngine implements PdfEngine
{
    public function available(): bool
    {
        return true;
    }

    public function render(string $html): string
    {
        return '%PDF-1.7 fake';
    }
}
