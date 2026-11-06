<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Http\Controllers\Admin\ReportCardIdentityController;
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
use App\Services\ReportCardIdentityService;
use App\Services\ReportCardWorkflowService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportCardIdentityTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::get('/__test/report-cards/{report_card}/identity', [ReportCardIdentityController::class, 'edit']);
            Route::put('/__test/report-cards/{report_card}/identity', [ReportCardIdentityController::class, 'update']);
        });
    }

    public function test_authenticated_surface_displays_report_card_identity(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['admin'])
            ->get('/__test/report-cards/'.$fixture['reportCard']->getKey().'/identity')
            ->assertOk()
            ->assertSee('Identitas dan nomor dokumen')
            ->assertSee('Siti Aminah')
            ->assertSee('NIPD-001')
            ->assertSee('1234567890')
            ->assertSee('Kelas 1')
            ->assertSee('Semester Ganjil')
            ->assertSee('2025/2026')
            ->assertSee('PKBM Harapan');
    }

    public function test_document_number_is_validated_and_persisted(): void
    {
        $fixture = $this->fixture();
        $url = '/__test/report-cards/'.$fixture['reportCard']->getKey().'/identity';

        $this->actingAs($fixture['admin'])->from($url)->put($url, [
            'document_number' => 'RAPOR#2026',
        ])->assertRedirect($url)->assertSessionHasErrors('document_number');

        $this->assertDatabaseHas('report_cards', [
            'id' => $fixture['reportCard']->getKey(),
            'document_number' => null,
        ]);

        $this->actingAs($fixture['admin'])->put($url, [
            'document_number' => ' RAPOR/2025-2026/001 ',
        ])->assertRedirect($url)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('report_cards', [
            'id' => $fixture['reportCard']->getKey(),
            'document_number' => 'RAPOR/2025-2026/001',
        ]);
    }

    public function test_locking_snapshots_identity_and_locked_surface_stays_immutable(): void
    {
        $fixture = $this->fixture();
        $fixture['reportCard']->update(['status' => 'APPROVED', 'document_number' => 'RAPOR-001']);

        $locked = app(ReportCardWorkflowService::class)->lock($fixture['reportCard']->refresh(), $fixture['admin']);

        $this->assertSame('LOCKED', $locked->status);
        $snapshot = json_decode((string) $locked->getRawOriginal('identity_snapshot'), true);
        $this->assertSame('Siti Aminah', $snapshot['student']['name']);
        $this->assertSame('Kelas 1', $snapshot['class']['name']);
        $this->assertSame('PKBM Harapan', $snapshot['institution']['name']);
        $this->assertSame('RAPOR-001', $snapshot['document_number']);

        $fixture['student']->update([
            'full_name' => 'Nama Master Berubah',
            'student_number' => 'NIPD-999',
            'national_student_number' => '9999999999',
        ]);
        $fixture['class']->update(['name' => 'Kelas Baru']);
        $fixture['institution']->update(['name' => 'PKBM Baru']);
        $fixture['academicYear']->update(['name' => '2030/2031']);

        $context = app(ReportCardIdentityService::class)->context($locked->refresh());

        $this->assertTrue($context['is_locked']);
        $this->assertSame('Siti Aminah', $context['student']['name']);
        $this->assertSame('NIPD-001', $context['student']['student_number']);
        $this->assertSame('Kelas 1', $context['class']['name']);
        $this->assertSame('2025/2026', $context['academic_year']['name']);
        $this->assertSame('PKBM Harapan', $context['institution']['name']);

        $url = '/__test/report-cards/'.$locked->getKey().'/identity';
        $this->actingAs($fixture['admin'])->put($url, ['document_number' => 'TIDAK-BOLEH'])
            ->assertForbidden();
        $this->assertSame('RAPOR-001', $locked->refresh()->document_number);
    }

    /** @return array<string, mixed> */
    private function fixture(): array
    {
        $institution = Institution::query()->create([
            'name' => 'PKBM Harapan',
            'address' => 'Jl. Pendidikan No. 1',
            'npsn' => 'P1234567',
        ]);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);
        $academicYear = AcademicYear::query()->create([
            'institution_id' => $institution->getKey(),
            'name' => '2025/2026',
            'status' => 'ACTIVE',
        ]);
        $semester = Semester::query()->create([
            'academic_year_id' => $academicYear->getKey(),
            'code' => 'GANJIL',
            'name' => 'Semester Ganjil',
            'sequence' => 1,
            'status' => 'ACTIVE',
        ]);
        $program = Program::query()->create([
            'institution_id' => $institution->getKey(),
            'code' => 'A',
            'name' => 'Paket A',
        ]);
        $level = Level::query()->create([
            'program_id' => $program->getKey(),
            'number' => 1,
            'name' => 'Kelas 1',
        ]);
        $class = AcademicClass::query()->create([
            'academic_year_id' => $academicYear->getKey(),
            'program_id' => $program->getKey(),
            'level_id' => $level->getKey(),
            'code' => 'A-1',
            'name' => 'Kelas 1',
        ]);
        $student = Student::query()->create([
            'institution_id' => $institution->getKey(),
            'student_number' => 'NIPD-001',
            'national_student_number' => '1234567890',
            'full_name' => 'Siti Aminah',
        ]);
        $enrollment = Enrollment::query()->create([
            'student_id' => $student->getKey(),
            'class_id' => $class->getKey(),
            'academic_year_id' => $academicYear->getKey(),
            'start_date' => '2025-07-01',
        ]);
        $reportCard = ReportCard::query()->create([
            'enrollment_id' => $enrollment->getKey(),
            'semester_id' => $semester->getKey(),
            'status' => 'DRAFT',
        ]);

        return compact('institution', 'admin', 'academicYear', 'semester', 'program', 'level', 'class', 'student', 'enrollment', 'reportCard');
    }
}
