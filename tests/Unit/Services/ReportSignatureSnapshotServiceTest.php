<?php

namespace Tests\Unit\Services;

use App\Enums\ReportSignerType;
use App\Enums\RoleCode;
use App\Enums\SignatorySource;
use App\Exceptions\MissingReportSignatory;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\InstitutionSignatorySetting;
use App\Models\Level;
use App\Models\Program;
use App\Models\ReportCard;
use App\Models\ReportSignature;
use App\Models\Semester;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\User;
use App\Services\Reports\ReportSignatureSnapshotService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportSignatureSnapshotServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_locked_report_creates_idempotent_snapshots_from_configured_accounts_and_guardian(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Snapshot']);
        $headmaster = User::factory()->withRole(RoleCode::Kepsek)->create([
            'institution_id' => $institution->getKey(),
            'name' => 'Kepala Awal',
        ]);
        $teacher = User::factory()->withRole(RoleCode::Guru)->create([
            'institution_id' => $institution->getKey(),
            'name' => 'Guru Wali',
        ]);
        StaffProfile::query()->create([
            'user_id' => $headmaster->getKey(),
            'employee_number' => 'NIY-HEAD-1',
            'title' => 'Kepala PKBM',
        ]);
        StaffProfile::query()->create([
            'user_id' => $teacher->getKey(),
            'employee_number' => 'NIP-GURU-1',
            'title' => 'Guru Kelas 1',
        ]);
        InstitutionSignatorySetting::query()->create([
            'institution_id' => $institution->getKey(),
            'headmaster_source' => SignatorySource::Account,
            'headmaster_user_id' => $headmaster->getKey(),
        ]);

        $reportCard = $this->lockedReportCard($institution, $teacher, 'Nama Wali');
        $service = app(ReportSignatureSnapshotService::class);

        $created = $service->snapshotForLockedReportCard($reportCard, now());
        $service->snapshotForLockedReportCard($reportCard->refresh(), now()->addMinute());

        $this->assertCount(3, $created);
        $this->assertSame(3, ReportSignature::query()->where('report_card_id', $reportCard->getKey())->count());
        $this->assertDatabaseHas('report_signatures', [
            'report_card_id' => $reportCard->getKey(),
            'signer_type' => ReportSignerType::HeadOfPkbm->value,
            'user_id' => $headmaster->getKey(),
            'signer_name_snapshot' => 'Kepala Awal',
            'signer_employee_number_snapshot' => 'NIY-HEAD-1',
        ]);
        $this->assertDatabaseHas('report_signatures', [
            'report_card_id' => $reportCard->getKey(),
            'signer_type' => ReportSignerType::HomeroomTeacher->value,
            'user_id' => $teacher->getKey(),
            'signer_name_snapshot' => 'Guru Wali',
            'signer_employee_number_snapshot' => 'NIP-GURU-1',
        ]);
        $this->assertDatabaseHas('report_signatures', [
            'report_card_id' => $reportCard->getKey(),
            'signer_type' => ReportSignerType::ParentGuardian->value,
            'user_id' => null,
            'signer_name_snapshot' => 'Nama Wali',
        ]);

        $headmaster->update(['name' => 'Kepala Berubah']);
        $this->assertDatabaseHas('report_signatures', [
            'report_card_id' => $reportCard->getKey(),
            'signer_type' => ReportSignerType::HeadOfPkbm->value,
            'signer_name_snapshot' => 'Kepala Awal',
        ]);
    }

    public function test_locked_report_uses_manual_headmaster_and_skips_empty_guardian(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Manual Snapshot']);
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey()]);
        InstitutionSignatorySetting::query()->create([
            'institution_id' => $institution->getKey(),
            'headmaster_source' => SignatorySource::Manual,
            'headmaster_name' => 'Kepala Manual',
            'headmaster_title' => 'Penanggung Jawab PKBM',
            'headmaster_employee_number' => 'NIY-MANUAL',
        ]);

        $reportCard = $this->lockedReportCard($institution, $teacher, null);

        app(ReportSignatureSnapshotService::class)->snapshotForLockedReportCard($reportCard);

        $this->assertDatabaseHas('report_signatures', [
            'report_card_id' => $reportCard->getKey(),
            'signer_type' => ReportSignerType::HeadOfPkbm->value,
            'signer_name_snapshot' => 'Kepala Manual',
            'signer_title_snapshot' => 'Penanggung Jawab PKBM',
            'signer_employee_number_snapshot' => 'NIY-MANUAL',
            'user_id' => null,
        ]);
        $this->assertDatabaseCount('report_signatures', 2);
    }

    public function test_service_rejects_unlocked_report_before_creating_snapshots(): void
    {
        $reportCard = new ReportCard(['status' => 'DRAFT']);

        $this->expectException(MissingReportSignatory::class);
        app(ReportSignatureSnapshotService::class)->snapshotForLockedReportCard($reportCard);
    }

    private function lockedReportCard(Institution $institution, User $teacher, ?string $guardianName): ReportCard
    {
        $academicYear = AcademicYear::query()->create([
            'institution_id' => $institution->getKey(),
            'name' => '2025/2026',
        ]);
        $program = Program::query()->create([
            'institution_id' => $institution->getKey(),
            'code' => 'PAKET-A',
            'name' => 'Paket A',
        ]);
        $level = Level::query()->create([
            'program_id' => $program->getKey(),
            'number' => 1,
            'name' => 'Kelas 1',
        ]);
        $academicClass = AcademicClass::query()->create([
            'academic_year_id' => $academicYear->getKey(),
            'program_id' => $program->getKey(),
            'level_id' => $level->getKey(),
            'homeroom_teacher_id' => $teacher->getKey(),
            'code' => 'KELAS-1',
            'name' => 'Kelas 1',
        ]);
        $student = Student::query()->create([
            'institution_id' => $institution->getKey(),
            'student_number' => 'PD-001',
            'full_name' => 'Peserta Didik',
            'guardian_name' => $guardianName,
        ]);
        $enrollment = Enrollment::query()->create([
            'student_id' => $student->getKey(),
            'class_id' => $academicClass->getKey(),
            'academic_year_id' => $academicYear->getKey(),
            'start_date' => '2025-07-01',
        ]);
        $semester = Semester::query()->create([
            'academic_year_id' => $academicYear->getKey(),
            'code' => 'GANJIL',
            'name' => 'Ganjil',
            'sequence' => 1,
        ]);

        return ReportCard::query()->create([
            'enrollment_id' => $enrollment->getKey(),
            'semester_id' => $semester->getKey(),
            'status' => 'LOCKED',
            'locked_at' => now(),
        ]);
    }
}
