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
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_access_only_records_in_their_institution(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM A']);
        $otherInstitution = Institution::query()->create(['name' => 'PKBM B']);
        $admin = $this->userWithRole($institution, RoleCode::Admin);

        $this->assertTrue(Gate::forUser($admin)->allows('view', $institution));
        $this->assertFalse(Gate::forUser($admin)->allows('view', $otherInstitution));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $institution));
    }

    public function test_guru_can_view_only_assigned_classes_students_and_subject_assignments(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Guru']);
        $guru = $this->userWithRole($institution, RoleCode::Guru);
        $otherGuru = $this->userWithRole($institution, RoleCode::Guru);
        $structure = $this->academicStructure($institution, $guru, 'A');
        $otherStructure = $this->academicStructure($institution, $otherGuru, 'B');

        $this->assertTrue(Gate::forUser($guru)->allows('view', $structure['class']));
        $this->assertTrue(Gate::forUser($guru)->allows('view', $structure['student']));
        $this->assertTrue(Gate::forUser($guru)->allows('view', $structure['assignment']));
        $this->assertFalse(Gate::forUser($guru)->allows('view', $otherStructure['class']));
        $this->assertFalse(Gate::forUser($guru)->allows('view', $otherStructure['student']));
        $this->assertFalse(Gate::forUser($guru)->allows('view', $otherStructure['assignment']));
        $this->assertFalse(Gate::forUser($guru)->allows('update', $structure['class']));
    }

    public function test_kepsek_can_review_and_approve_but_cannot_lock_or_unlock_report_cards(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Kepsek']);
        $guru = $this->userWithRole($institution, RoleCode::Guru);
        $kepsek = $this->userWithRole($institution, RoleCode::Kepsek);
        $admin = $this->userWithRole($institution, RoleCode::Admin);
        $structure = $this->academicStructure($institution, $guru, 'A');
        $reportCard = $structure['reportCard'];

        $this->assertTrue(Gate::forUser($kepsek)->allows('view', $reportCard));
        $this->assertTrue(Gate::forUser($kepsek)->allows('review', $reportCard));
        $this->assertTrue(Gate::forUser($kepsek)->allows('approve', $reportCard));
        $this->assertFalse(Gate::forUser($kepsek)->allows('lock', $reportCard));
        $this->assertFalse(Gate::forUser($kepsek)->allows('unlock', $reportCard));

        $reportCard->update(['status' => 'APPROVED']);
        $this->assertTrue(Gate::forUser($admin)->allows('lock', $reportCard->refresh()));

        $reportCard->update(['status' => 'LOCKED']);
        $this->assertTrue(Gate::forUser($admin)->allows('unlock', $reportCard->refresh()));
        $this->assertFalse(Gate::forUser($guru)->allows('update', $reportCard->refresh()));
    }

    private function userWithRole(Institution $institution, RoleCode $role): User
    {
        return User::factory()->withRole($role)->create(['institution_id' => $institution->getKey()]);
    }

    /**
     * @return array{class: AcademicClass, student: Student, assignment: SubjectAssignment, reportCard: ReportCard}
     */
    private function academicStructure(Institution $institution, User $teacher, string $suffix): array
    {
        $academicYear = AcademicYear::query()->create([
            'institution_id' => $institution->getKey(),
            'name' => '2025/2026 '.$suffix,
        ]);
        $program = Program::query()->create([
            'institution_id' => $institution->getKey(),
            'code' => 'PAKET-'.$suffix,
            'name' => 'Paket '.$suffix,
        ]);
        $level = Level::query()->create([
            'program_id' => $program->getKey(),
            'number' => 1,
            'name' => 'Tingkat 1',
        ]);
        $academicClass = AcademicClass::query()->create([
            'academic_year_id' => $academicYear->getKey(),
            'program_id' => $program->getKey(),
            'level_id' => $level->getKey(),
            'homeroom_teacher_id' => $teacher->getKey(),
            'code' => 'KELAS-'.$suffix,
            'name' => 'Kelas '.$suffix,
        ]);
        $subject = Subject::query()->create([
            'institution_id' => $institution->getKey(),
            'code' => 'MAPEL-'.$suffix,
            'name' => 'Mata Pelajaran '.$suffix,
            'short_name' => 'MP'.$suffix,
        ]);
        $assignment = SubjectAssignment::query()->create([
            'class_id' => $academicClass->getKey(),
            'subject_id' => $subject->getKey(),
            'teacher_id' => $teacher->getKey(),
        ]);
        $student = Student::query()->create([
            'institution_id' => $institution->getKey(),
            'student_number' => 'PD-'.$suffix,
            'full_name' => 'Peserta Didik '.$suffix,
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
        $reportCard = ReportCard::query()->create([
            'enrollment_id' => $enrollment->getKey(),
            'semester_id' => $semester->getKey(),
            'status' => 'IN_REVIEW',
        ]);

        return [
            'class' => $academicClass,
            'student' => $student,
            'assignment' => $assignment,
            'reportCard' => $reportCard,
        ];
    }
}
