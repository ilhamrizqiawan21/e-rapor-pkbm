<?php

namespace Tests\Unit\Services;

use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\AttendanceSummary;
use App\Models\AttitudeAssessment;
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
use App\Services\ReportCardWorkflowService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReportCardWorkflowServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_archive_a_complete_draft_and_safely_unlock_it(): void
    {
        $fixture = $this->fixture();
        $service = app(ReportCardWorkflowService::class);
        $reportCard = $fixture['reportCard'];

        $locked = $service->lock($reportCard, $fixture['admin']);
        $this->assertSame('LOCKED', $locked->status);
        $this->assertNotNull($locked->locked_at);
        $this->assertSame($fixture['admin']->id, $locked->locked_by_user_id);

        $unlocked = $service->unlock($locked, $fixture['admin'], 'Perbaikan administrasi nomor dokumen.');
        $this->assertSame('DRAFT', $unlocked->status);
        $this->assertNull($unlocked->locked_at);
        $this->assertSame($fixture['admin']->id, $unlocked->unlocked_by_user_id);
        $this->assertSame('Perbaikan administrasi nomor dokumen.', $unlocked->unlock_reason);

        $this->assertDatabaseCount('audit_logs', 2);
        $this->assertDatabaseHas('audit_logs', [
            'report_card_id' => $reportCard->id,
            'action' => 'report_card.unlocked',
            'reason' => 'Perbaikan administrasi nomor dokumen.',
        ]);
    }

    public function test_incomplete_report_card_cannot_be_archived(): void
    {
        $fixture = $this->fixture();
        $service = app(ReportCardWorkflowService::class);

        $this->expectException(ValidationException::class);
        $fixture['reportCard']->update(['document_number' => null]);
        $service->lock($fixture['reportCard'], $fixture['admin']);
    }

    public function test_unlock_requires_reason_and_illegal_status_transitions_are_rejected(): void
    {
        $fixture = $this->fixture();
        $service = app(ReportCardWorkflowService::class);

        $this->expectException(ValidationException::class);
        $service->unlock($fixture['reportCard']->forceFill(['status' => 'LOCKED']), $fixture['admin']);
    }

    public function test_approval_workflow_abilities_are_no_longer_available(): void
    {
        $fixture = $this->fixture();
        $reportCard = $fixture['reportCard'];

        $this->assertFalse(Gate::forUser($fixture['guru'])->allows('submit', $reportCard));
        $this->assertFalse(Gate::forUser($fixture['guru'])->allows('review', $reportCard));
        $this->assertFalse(Gate::forUser($fixture['kepsek'])->allows('reject', $reportCard));
        $this->assertFalse(Gate::forUser($fixture['kepsek'])->allows('approve', $reportCard));
        $this->assertFalse(Gate::forUser($fixture['kepsek'])->allows('lock', $reportCard));
        $this->assertTrue(Gate::forUser($fixture['admin'])->allows('lock', $reportCard->refresh()));
        $this->assertFalse(Gate::forUser($fixture['guru'])->allows('unlock', $reportCard->refresh()->forceFill(['status' => 'LOCKED'])));
    }

    /** @return array{institution: Institution, guru: User, kepsek: User, admin: User, reportCard: ReportCard} */
    private function fixture(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Workflow']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $kepsek = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $institution->id]);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2025/2026']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'PAKET-WF', 'name' => 'Paket Workflow']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create([
            'academic_year_id' => $year->id,
            'program_id' => $program->id,
            'level_id' => $level->id,
            'homeroom_teacher_id' => $guru->id,
            'code' => 'WF-1',
            'name' => 'Kelas Workflow',
        ]);
        $subject = Subject::query()->create([
            'institution_id' => $institution->id,
            'code' => 'WF-MP',
            'name' => 'Mapel Workflow',
            'short_name' => 'WF',
        ]);
        $assignment = SubjectAssignment::query()->create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $guru->id,
        ]);
        $student = Student::query()->create([
            'institution_id' => $institution->id,
            'student_number' => 'WF-001',
            'full_name' => 'Siswa Workflow',
        ]);
        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
            'start_date' => '2025-07-01',
        ]);
        $semester = Semester::query()->create([
            'academic_year_id' => $year->id,
            'code' => 'GANJIL',
            'name' => 'Semester Ganjil',
            'sequence' => 1,
        ]);
        $reportCard = ReportCard::query()->create([
            'enrollment_id' => $enrollment->id,
            'semester_id' => $semester->id,
            'status' => 'DRAFT',
            'document_number' => 'RAPOR-WF-001',
        ]);

        AttitudeAssessment::query()->create(['report_card_id' => $reportCard->id, 'attitude_type' => 'SPIRITUAL', 'description' => 'Menunjukkan sikap spiritual yang baik.']);
        AttitudeAssessment::query()->create(['report_card_id' => $reportCard->id, 'attitude_type' => 'SOCIAL', 'description' => 'Mampu bekerja sama dengan baik.']);
        AttendanceSummary::query()->create(['report_card_id' => $reportCard->id]);
        ReportGrade::query()->create([
            'report_card_id' => $reportCard->id,
            'subject_assignment_id' => $assignment->id,
            'subject_name_snapshot' => 'Mapel Workflow',
            'subject_short_name_snapshot' => 'WF',
            'minimum_score_snapshot' => 70,
            'display_order_snapshot' => 1,
            'score' => 85,
            'predicate' => 'A',
            'description' => 'Menguasai materi dengan baik.',
        ]);

        return compact('institution', 'guru', 'kepsek', 'admin', 'reportCard');
    }
}
