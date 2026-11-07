<?php

namespace Tests\Unit\Services;

use App\Contracts\PdfEngine;
use App\Enums\RoleCode;
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
use App\Models\ReportGrade;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\Reports\LedgerExportService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class LedgerExportServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_ledger_uses_only_printable_report_cards_and_follows_reporting_enrollment(): void
    {
        $fixture = $this->fixture();
        $source = $this->enrollment($fixture['student'], $fixture['source'], $fixture['year'], '2026-07-01', 'TRANSFERRED');
        $target = $this->enrollment($fixture['student'], $fixture['class'], $fixture['year'], '2026-09-16', 'ACTIVE', $source->id);
        $sourceAssignment = $this->assignment($fixture['source'], $fixture['subject'], $fixture['admin'], 'MAT-SRC');
        $card = ReportCard::query()->create(['enrollment_id' => $source->id, 'semester_id' => $fixture['semester']->id, 'status' => 'DRAFT', 'document_number' => 'RAPOR-001']);
        ReportGrade::query()->create([
            'report_card_id' => $card->id,
            'subject_assignment_id' => $sourceAssignment->id,
            'subject_name_snapshot' => 'Matematika',
            'subject_short_name_snapshot' => 'MAT',
            'minimum_score_snapshot' => 70,
            'display_order_snapshot' => 1,
            'score' => 88,
            'predicate' => 'B',
            'description' => 'Baik.',
            'status' => 'DRAFT',
        ]);
        AttendanceSummary::query()->create(['report_card_id' => $card->id, 'sick_days' => 1, 'leave_days' => 2, 'unexcused_days' => 0]);
        AttitudeAssessment::query()->create(['report_card_id' => $card->id, 'attitude_type' => 'SPIRITUAL', 'description' => 'Baik.']);
        AttitudeAssessment::query()->create(['report_card_id' => $card->id, 'attitude_type' => 'SOCIAL', 'description' => 'Baik.']);
        HomeroomNote::query()->create(['report_card_id' => $card->id, 'note' => 'Pertahankan.']);

        $draftEnrollment = $this->enrollment(Student::query()->create([
            'institution_id' => $fixture['institution']->id,
            'student_number' => 'NIPD-002',
            'full_name' => 'Draft Only',
        ]), $fixture['class'], $fixture['year']);
        ReportCard::query()->create(['enrollment_id' => $draftEnrollment->id, 'semester_id' => $fixture['semester']->id, 'status' => 'DRAFT']);

        $data = app(LedgerExportService::class)->classData($fixture['admin'], $fixture['class'], $fixture['semester']);

        $this->assertSame('Kelas Tujuan', $data['class']);
        $this->assertSame(2, $data['enrollment_count']);
        $this->assertSame(1, $data['report_card_count']);
        $this->assertSame('NIPD-001', $data['rows'][0]['student_number']);
        $this->assertSame(88, $data['rows'][0]['scores']['assignment:'.$sourceAssignment->id]);
        $this->assertSame('MAT', $data['subjects'][0]['label']);
        $this->assertSame('Baik.', $data['details'][0]['description']);
    }

    public function test_excel_is_a_valid_native_xlsx_with_summary_main_and_detail_sheets(): void
    {
        $fixture = $this->fixture();
        $enrollment = $this->enrollment($fixture['student'], $fixture['class'], $fixture['year']);
        $assignment = $this->assignment($fixture['class'], $fixture['subject'], $fixture['admin'], 'MAT');
        $card = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $fixture['semester']->id, 'status' => 'LOCKED', 'document_number' => 'RAPOR-002']);
        ReportGrade::query()->create([
            'report_card_id' => $card->id,
            'subject_assignment_id' => $assignment->id,
            'subject_name_snapshot' => 'Matematika',
            'subject_short_name_snapshot' => 'MAT',
            'minimum_score_snapshot' => 70,
            'display_order_snapshot' => 1,
            'score' => 90,
            'predicate' => 'A',
            'description' => 'Sangat baik.',
            'status' => 'DRAFT',
        ]);
        AttendanceSummary::query()->create(['report_card_id' => $card->id, 'sick_days' => 0, 'leave_days' => 0, 'unexcused_days' => 0]);
        AttitudeAssessment::query()->create(['report_card_id' => $card->id, 'attitude_type' => 'SPIRITUAL', 'description' => 'Baik.']);
        AttitudeAssessment::query()->create(['report_card_id' => $card->id, 'attitude_type' => 'SOCIAL', 'description' => 'Baik.']);
        $extraSubject = Subject::query()->create(['institution_id' => $fixture['institution']->id, 'code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam', 'short_name' => 'IPA']);
        $this->assignment($fixture['class'], $extraSubject, $fixture['admin'], 'IPA');

        $classData = app(LedgerExportService::class)->classData($fixture['admin'], $fixture['class'], $fixture['semester']);
        $this->assertContains('IPA', array_column($classData['subjects'], 'label'));

        $export = app(LedgerExportService::class)->excel($fixture['admin'], $fixture['semester'], $fixture['class']);
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($export['path']) === true);
        $workbook = $zip->getFromName('xl/workbook.xml');
        $this->assertIsString($workbook);
        $this->assertStringContainsString('Summary', $workbook);
        $this->assertStringContainsString('Leger Kelas Tujuan', $workbook);
        $this->assertStringContainsString('Detail Kelas Tujuan', $workbook);
        $this->assertNotFalse($zip->getFromName('xl/styles.xml'));
        $zip->close();
        @unlink($export['path']);
    }

    public function test_guru_all_classes_export_is_limited_to_assigned_classes(): void
    {
        $fixture = $this->fixture();
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $fixture['institution']->id]);
        $fixture['class']->update(['homeroom_teacher_id' => $guru->id]);
        $other = AcademicClass::query()->create([
            'academic_year_id' => $fixture['year']->id,
            'program_id' => $fixture['program']->id,
            'level_id' => $fixture['level']->id,
            'code' => 'B-1',
            'name' => 'Kelas Lain',
        ]);

        $data = app(LedgerExportService::class)->allData($guru, $fixture['semester']);

        $this->assertCount(1, $data['classes']);
        $this->assertSame($fixture['class']->id, AcademicClass::query()->where('name', $data['classes'][0]['class'])->value('id'));
        $this->assertNotSame($other->id, AcademicClass::query()->where('name', $data['classes'][0]['class'])->value('id'));
    }

    public function test_pdf_uses_the_same_ledger_payload_and_deterministic_filename(): void
    {
        $fixture = $this->fixture();
        app()->instance(PdfEngine::class, new LedgerFakePdfEngine);

        $export = app(LedgerExportService::class)->pdf($fixture['admin'], $fixture['semester'], $fixture['class']);

        $this->assertSame('%PDF-1.7 ledger', $export['contents']);
        $this->assertSame('leger-2025-2026-ganjil-a-1.pdf', $export['filename']);
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
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas Tujuan']);
        $source = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-0', 'name' => 'Kelas Asal']);
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'NIPD-001', 'national_student_number' => 'NISN-001', 'full_name' => 'Peserta Ledger']);
        $subject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MAT']);

        return compact('institution', 'admin', 'year', 'semester', 'program', 'level', 'class', 'source', 'student', 'subject');
    }

    private function enrollment(Student $student, AcademicClass $class, AcademicYear $year, string $date = '2026-07-01', string $status = 'ACTIVE', ?int $reportingId = null): Enrollment
    {
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => $date, 'enrollment_status' => $status]);
        $enrollment->update(['reporting_enrollment_id' => $reportingId ?: $enrollment->id]);

        return $enrollment->refresh();
    }

    private function assignment(AcademicClass $class, Subject $subject, User $teacher, string $code): SubjectAssignment
    {
        return SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
    }
}

final class LedgerFakePdfEngine implements PdfEngine
{
    public function available(): bool
    {
        return true;
    }

    public function render(string $html): string
    {
        return '%PDF-1.7 ledger';
    }
}
