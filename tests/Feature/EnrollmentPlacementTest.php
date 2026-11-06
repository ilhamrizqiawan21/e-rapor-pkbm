<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Http\Controllers\Admin\ClassTransferController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnrollmentPlacementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::resource('admin/academic-classes.enrollments', EnrollmentController::class)
            ->only(['index', 'create', 'store'])
            ->parameters(['academic-classes' => 'academic_class'])
            ->names([
                'index' => 'admin.academic-classes.enrollments.index',
                'create' => 'admin.academic-classes.enrollments.create',
                'store' => 'admin.academic-classes.enrollments.store',
            ])
            ->middleware(['web', 'auth']);
        Route::resource('admin/enrollments', EnrollmentController::class)
            ->only(['show', 'destroy'])
            ->names([
                'show' => 'admin.enrollments.show',
                'destroy' => 'admin.enrollments.withdraw',
            ])
            ->middleware(['web', 'auth']);
        Route::resource('admin/enrollments.transfers', ClassTransferController::class)
            ->only(['index', 'create', 'store'])
            ->names([
                'index' => 'admin.enrollments.transfers.index',
                'create' => 'admin.enrollments.transfers.create',
                'store' => 'admin.enrollments.transfers.store',
            ])
            ->middleware(['web', 'auth']);
    }

    public function test_admin_can_enroll_and_cannot_duplicate_active_placement(): void
    {
        [$admin, $year, $source, $target, $student] = $this->academicSetup();

        $this->actingAs($admin)->post(route('admin.academic-classes.enrollments.store', $source), [
            'student_id' => $student->id,
            'start_date' => '2026-07-15',
            'notes' => 'Masuk awal tahun',
        ])->assertRedirect();

        $enrollment = Enrollment::query()->firstOrFail();
        $this->assertSame($enrollment->id, $enrollment->reporting_enrollment_id);

        $this->actingAs($admin)->from(route('admin.academic-classes.enrollments.create', $target))
            ->post(route('admin.academic-classes.enrollments.store', $target), [
                'student_id' => $student->id,
                'start_date' => '2026-07-15',
            ])->assertRedirect(route('admin.academic-classes.enrollments.create', $target))
            ->assertSessionHasErrors('student_id');
    }

    public function test_withdrawal_keeps_history_and_records_audit(): void
    {
        [$admin, $year, $source, $target, $student] = $this->academicSetup();
        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'class_id' => $source->id,
            'academic_year_id' => $year->id,
            'start_date' => '2026-07-01',
            'enrollment_status' => 'ACTIVE',
        ]);

        $this->actingAs($admin)->delete(route('admin.enrollments.withdraw', $enrollment), [
            'end_date' => '2026-08-01',
            'reason' => 'Pindah domisili',
        ])->assertRedirect(route('admin.enrollments.show', $enrollment));

        $this->assertSame('WITHDRAWN', $enrollment->fresh()->enrollment_status);
        $this->assertSame('2026-08-01', $enrollment->fresh()->end_date->toDateString());
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.enrollment_withdrawn', 'entity_id' => $enrollment->id]);
    }

    public function test_transfer_closes_source_creates_target_and_preserves_selected_reporting_source(): void
    {
        [$admin, $year, $source, $target, $student] = $this->academicSetup();
        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'class_id' => $source->id,
            'academic_year_id' => $year->id,
            'start_date' => '2026-07-01',
            'enrollment_status' => 'ACTIVE',
        ]);

        $this->actingAs($admin)->post(route('admin.enrollments.transfers.store', $enrollment), [
            'target_class_id' => $target->id,
            'effective_date' => '2026-09-01',
            'reason' => 'Penyesuaian program',
            'notes' => 'Disetujui rapat',
            'reporting_enrollment_id' => (string) $enrollment->id,
        ])->assertRedirect();

        $newEnrollment = Enrollment::query()->where('class_id', $target->id)->firstOrFail();
        $this->assertSame('TRANSFERRED', $enrollment->fresh()->enrollment_status);
        $this->assertSame('2026-09-01', $enrollment->fresh()->end_date->toDateString());
        $this->assertSame($enrollment->id, $newEnrollment->reporting_enrollment_id);
        $this->assertDatabaseHas('class_transfers', ['from_enrollment_id' => $enrollment->id, 'to_enrollment_id' => $newEnrollment->id, 'approved_by_admin_id' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.class_transferred']);
    }

    public function test_closed_period_rejects_enrollment_changes_and_non_admin_cannot_approve_transfer(): void
    {
        [$admin, $year, $source, $target, $student, $guru] = $this->academicSetup(['status' => 'CLOSED']);
        $this->actingAs($admin)->post(route('admin.academic-classes.enrollments.store', $source), [
            'student_id' => $student->id,
            'start_date' => '2026-07-01',
        ])->assertStatus(422);

        $openYear = AcademicYear::query()->create(['institution_id' => $admin->institution_id, 'name' => '2027/2028', 'status' => 'ACTIVE']);
        $source->update(['academic_year_id' => $openYear->id]);
        $target->update(['academic_year_id' => $openYear->id]);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $source->id, 'academic_year_id' => $openYear->id, 'start_date' => '2027-07-01', 'enrollment_status' => 'ACTIVE']);

        $this->actingAs($guru)->get(route('admin.enrollments.show', $enrollment))->assertForbidden();
    }

    public function test_teacher_can_view_enrollment_timeline_for_assigned_class(): void
    {
        [$admin, $year, $source, $target, $student, $guru] = $this->academicSetup();
        $source->update(['homeroom_teacher_id' => $guru->id]);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $source->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);

        $this->actingAs($guru)->get(route('admin.enrollments.show', $enrollment))
            ->assertOk()
            ->assertSee($student->full_name)
            ->assertSee($source->name);
    }

    /**
     * @return array{0: User, 1: AcademicYear, 2: AcademicClass, 3: AcademicClass, 4: Student, 5: User}
     */
    private function academicSetup(array $yearAttributes = []): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Placement Uji']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(array_merge(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE'], $yearAttributes));
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $source = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas Asal', 'status' => 'ACTIVE']);
        $target = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-2', 'name' => 'Kelas Tujuan', 'status' => 'ACTIVE']);
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-001', 'full_name' => 'Siswa Placement', 'status' => 'ACTIVE']);

        return [$admin, $year, $source, $target, $student, $guru];
    }
}
