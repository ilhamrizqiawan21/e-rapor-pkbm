<?php

namespace Tests\Unit\Services;

use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\HomeroomAssignment;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\ReportCard;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\HomeroomAssignmentService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HomeroomAssignmentServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_assign_sets_current_pointer_and_creates_history(): void
    {
        [$admin, $class, $firstTeacher] = $this->context();

        $assignment = app(HomeroomAssignmentService::class)->assign($class, $firstTeacher, $admin);

        $this->assertSame($firstTeacher->getKey(), $class->refresh()->homeroom_teacher_id);
        $this->assertTrue($assignment->isActive());
        $this->assertDatabaseHas('homeroom_assignments', [
            'academic_class_id' => $class->getKey(),
            'teacher_id' => $firstTeacher->getKey(),
            'assigned_by_id' => $admin->getKey(),
            'status' => 'ACTIVE',
            'ended_at' => null,
        ]);
    }

    public function test_reassign_closes_previous_history_and_keeps_one_active_assignment(): void
    {
        [$admin, $class, $firstTeacher, $secondTeacher] = $this->context();
        $service = app(HomeroomAssignmentService::class);
        $first = $service->assign($class, $firstTeacher, $admin);

        $second = $service->reassign($class, $secondTeacher, $admin);

        $this->assertNotSame($first->getKey(), $second->getKey());
        $this->assertNotNull($first->refresh()->ended_at);
        $this->assertSame('ENDED', $first->status);
        $this->assertSame($secondTeacher->getKey(), $class->refresh()->homeroom_teacher_id);
        $this->assertSame(1, HomeroomAssignment::query()->where('academic_class_id', $class->getKey())->where('status', 'ACTIVE')->whereNull('ended_at')->count());
        $this->assertSame(2, HomeroomAssignment::query()->where('academic_class_id', $class->getKey())->count());
    }

    public function test_remove_closes_history_and_clears_current_pointer(): void
    {
        [$admin, $class, $teacher] = $this->context();
        $service = app(HomeroomAssignmentService::class);
        $assignment = $service->assign($class, $teacher, $admin);

        $removed = $service->remove($class, $admin);

        $this->assertSame($assignment->getKey(), $removed?->getKey());
        $this->assertNull($class->refresh()->homeroom_teacher_id);
        $this->assertSame('ENDED', $assignment->refresh()->status);
        $this->assertNotNull($assignment->ended_at);
    }

    public function test_closed_period_rejects_assignment_without_mutating_class_or_history(): void
    {
        [$admin, $class, $teacher] = $this->context(['status' => 'CLOSED']);

        $this->expectException(ValidationException::class);
        app(HomeroomAssignmentService::class)->assign($class, $teacher, $admin);

        $this->assertNull($class->refresh()->homeroom_teacher_id);
        $this->assertDatabaseCount('homeroom_assignments', 0);
    }

    public function test_invalid_teacher_is_rejected_for_inactive_wrong_role_or_other_institution(): void
    {
        [$admin, $class] = $this->context();
        $inactive = User::factory()->withRole(RoleCode::Guru)->inactive()->create(['institution_id' => $admin->institution_id]);
        $kepsek = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $admin->institution_id]);
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);
        $otherTeacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $otherInstitution->getKey()]);

        foreach ([$inactive, $kepsek, $otherTeacher] as $teacher) {
            try {
                app(HomeroomAssignmentService::class)->assign($class, $teacher, $admin);
                $this->fail('Teacher tidak valid seharusnya ditolak.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('teacher', $exception->errors());
            }
        }

        $this->assertDatabaseCount('homeroom_assignments', 0);
    }

    public function test_locked_report_is_not_modified_when_assignment_changes(): void
    {
        [$admin, $class, $firstTeacher, $secondTeacher, $year, $institution] = $this->context();
        $semester = Semester::query()->create([
            'academic_year_id' => $year->getKey(),
            'code' => 'GANJIL',
            'name' => 'Semester Ganjil',
            'sequence' => 1,
            'status' => 'ACTIVE',
        ]);
        $student = Student::query()->create([
            'institution_id' => $institution->getKey(),
            'student_number' => 'S-001',
            'full_name' => 'Siswa Uji',
        ]);
        $enrollment = Enrollment::query()->create([
            'student_id' => $student->getKey(),
            'class_id' => $class->getKey(),
            'academic_year_id' => $year->getKey(),
            'start_date' => now()->toDateString(),
            'enrollment_status' => 'ACTIVE',
        ]);
        $report = ReportCard::query()->create([
            'enrollment_id' => $enrollment->getKey(),
            'semester_id' => $semester->getKey(),
            'document_number' => 'RAPOR-001',
            'status' => 'LOCKED',
            'locked_at' => now(),
        ]);

        $service = app(HomeroomAssignmentService::class);
        $service->assign($class, $firstTeacher, $admin);
        $service->reassign($class, $secondTeacher, $admin);

        $this->assertDatabaseHas('report_cards', [
            'id' => $report->getKey(),
            'document_number' => 'RAPOR-001',
            'status' => 'LOCKED',
        ]);
    }

    /** @return array<int, mixed> */
    private function context(array $yearAttributes = []): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Wali Uji']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);
        $firstTeacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey()]);
        $secondTeacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey()]);
        $year = AcademicYear::query()->create(array_merge([
            'institution_id' => $institution->getKey(),
            'name' => '2026/2027',
            'status' => 'ACTIVE',
        ], $yearAttributes));
        $program = Program::query()->create(['institution_id' => $institution->getKey(), 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->getKey(), 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create([
            'academic_year_id' => $year->getKey(),
            'program_id' => $program->getKey(),
            'level_id' => $level->getKey(),
            'code' => 'A-1',
            'name' => 'Kelas 1',
            'status' => 'ACTIVE',
        ]);

        return [$admin, $class, $firstTeacher, $secondTeacher, $year, $institution];
    }
}
