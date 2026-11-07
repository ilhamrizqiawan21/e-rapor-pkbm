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
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\ReportCardWorkflowService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StageTenAdminRoutesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_open_the_stage_ten_report_card_surfaces(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['admin'])
            ->get(route('admin.report-cards.index'))
            ->assertOk()
            ->assertSee('Kelengkapan & cetak rapor')
            ->assertSee('Antrean kelengkapan rapor')
            ->assertSee('Nama peserta didik')
            ->assertSee('Siswa Tahap Sepuluh');

        $this->actingAs($fixture['admin'])
            ->get(route('admin.report-cards.identity.edit', $fixture['reportCard']))
            ->assertOk()
            ->assertSee('Identitas dan nomor dokumen');

        $this->actingAs($fixture['admin'])
            ->get(route('admin.report-cards.assessments.show', $fixture['reportCard']))
            ->assertOk()
            ->assertSee('Sikap dan absensi');

        $this->actingAs($fixture['admin'])
            ->get(route('admin.report-cards.completeness.edit', $fixture['reportCard']))
            ->assertOk()
            ->assertSee('Ekstrakurikuler')
            ->assertSee('catatan wali kelas');
    }

    public function test_approval_is_rejected_when_required_components_are_missing(): void
    {
        $fixture = $this->fixture(['status' => 'IN_REVIEW']);

        $this->expectException(ValidationException::class);
        app(ReportCardWorkflowService::class)->approve($fixture['reportCard'], $fixture['kepsek']);
    }

    public function test_non_homeroom_teacher_cannot_change_document_number(): void
    {
        $fixture = $this->fixture();
        $otherGuru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $fixture['institution']->id]);

        $this->actingAs($otherGuru)
            ->put(route('admin.report-cards.identity.update', $fixture['reportCard']), ['document_number' => 'RAPOR-010'])
            ->assertForbidden();
    }

    /** @param array<string, mixed> $reportCardAttributes */
    private function fixture(array $reportCardAttributes = []): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Tahap Sepuluh']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $kepsek = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $institution->id]);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Semester Ganjil', 'sequence' => 1, 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'PAKET-A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Kelas 1']);
        $class = AcademicClass::query()->create([
            'academic_year_id' => $year->id,
            'program_id' => $program->id,
            'level_id' => $level->id,
            'homeroom_teacher_id' => $guru->id,
            'code' => 'A-1',
            'name' => 'Kelas 1',
            'status' => 'ACTIVE',
        ]);
        $student = Student::query()->create([
            'institution_id' => $institution->id,
            'student_number' => 'NIPD-010',
            'full_name' => 'Siswa Tahap Sepuluh',
            'status' => 'ACTIVE',
        ]);
        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
            'start_date' => '2026-07-01',
            'enrollment_status' => 'ACTIVE',
        ]);
        $reportCard = ReportCard::query()->create(array_merge([
            'enrollment_id' => $enrollment->id,
            'semester_id' => $semester->id,
            'status' => 'DRAFT',
        ], $reportCardAttributes));

        return compact('institution', 'admin', 'kepsek', 'guru', 'year', 'semester', 'program', 'level', 'class', 'student', 'enrollment', 'reportCard');
    }
}
