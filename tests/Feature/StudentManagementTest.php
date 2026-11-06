<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\StudentController;
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
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
            Route::resource('students', StudentController::class);
        });
    }

    public function test_admin_can_create_student_with_identity_data(): void
    {
        [$institution, $admin] = $this->users();

        $this->actingAs($admin)->post(route('admin.students.store'), [
            'student_number' => '  PD-001  ',
            'national_student_number' => '1234567890',
            'full_name' => '  Siswa Baru  ',
            'gender' => 'l',
            'birth_place' => 'Bandung',
            'birth_date' => '2012-04-10',
            'address' => 'Jalan PKBM',
            'guardian_name' => 'Orang Tua Siswa',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('students', [
            'institution_id' => $institution->id,
            'student_number' => 'PD-001',
            'national_student_number' => '1234567890',
            'full_name' => 'Siswa Baru',
            'gender' => 'L',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_student_identifiers_are_unique_per_institution_and_date_gender_are_validated(): void
    {
        [$institution, $admin] = $this->users();
        Student::query()->create([
            'institution_id' => $institution->id,
            'student_number' => 'PD-001',
            'national_student_number' => '1234567890',
            'full_name' => 'Siswa Lama',
            'gender' => 'L',
        ]);

        $this->actingAs($admin)->post(route('admin.students.store'), [
            'student_number' => 'PD-001',
            'national_student_number' => '1234567890',
            'full_name' => 'Siswa Duplikat',
            'gender' => 'X',
            'birth_date' => now()->addDay()->toDateString(),
            'status' => 'ACTIVE',
        ])->assertSessionHasErrors(['student_number', 'national_student_number', 'gender', 'birth_date']);
    }

    public function test_admin_can_search_filter_and_update_student(): void
    {
        [$institution, $admin] = $this->users();
        $student = Student::query()->create([
            'institution_id' => $institution->id,
            'student_number' => 'PD-001',
            'national_student_number' => '111',
            'full_name' => 'Siswa Aktif',
            'gender' => 'P',
            'status' => 'ACTIVE',
        ]);
        Student::query()->create([
            'institution_id' => $institution->id,
            'student_number' => 'PD-002',
            'full_name' => 'Siswa Nonaktif',
            'gender' => 'L',
            'status' => 'INACTIVE',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.students.index', ['search' => 'PD-001', 'status' => 'ACTIVE']))
            ->assertOk()
            ->assertSee('Siswa Aktif')
            ->assertDontSee('Siswa Nonaktif');

        $this->actingAs($admin)->put(route('admin.students.update', $student), [
            'student_number' => 'PD-001',
            'national_student_number' => '111',
            'full_name' => 'Siswa Diperbarui',
            'gender' => 'L',
            'status' => 'GRADUATED',
        ])->assertRedirect(route('admin.students.show', $student));

        $this->assertDatabaseHas('students', ['id' => $student->id, 'full_name' => 'Siswa Diperbarui', 'status' => 'GRADUATED']);
    }

    public function test_guru_and_kepsek_can_view_only_allowed_students_and_cannot_change_data(): void
    {
        [$institution, $admin, $guru, $kepsek] = $this->users();
        [$class, $year] = $this->classFor($institution, $guru);
        $assigned = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-001', 'full_name' => 'Siswa Kelas Guru', 'gender' => 'L']);
        $unassigned = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-002', 'full_name' => 'Siswa Lain', 'gender' => 'P']);
        Enrollment::query()->create(['student_id' => $assigned->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01']);

        $this->actingAs($guru)->get(route('admin.students.index'))->assertOk()->assertSee('Siswa Kelas Guru')->assertDontSee('Siswa Lain');
        $this->actingAs($guru)->get(route('admin.students.show', $assigned))->assertOk();
        $this->actingAs($guru)->get(route('admin.students.show', $unassigned))->assertForbidden();
        $this->actingAs($guru)->get(route('admin.students.create'))->assertForbidden();
        $this->actingAs($guru)->get(route('admin.students.edit', $assigned))->assertForbidden();

        $this->actingAs($kepsek)->get(route('admin.students.index'))->assertOk()->assertSee('Siswa Kelas Guru')->assertSee('Siswa Lain');
        $this->actingAs($kepsek)->get(route('admin.students.create'))->assertForbidden();
        $this->assertTrue($admin->isActive());
    }

    public function test_student_with_history_is_deactivated_without_losing_enrollment_or_report(): void
    {
        [$institution, $admin, $guru] = $this->users();
        [$class, $year] = $this->classFor($institution, $guru);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Semester Ganjil', 'sequence' => 1, 'status' => 'ACTIVE']);
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-001', 'full_name' => 'Siswa Berhistori', 'gender' => 'L']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01']);
        $report = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'LOCKED']);

        $this->actingAs($admin)->delete(route('admin.students.destroy', $student))->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'INACTIVE']);
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'student_id' => $student->id]);
        $this->assertDatabaseHas('report_cards', ['id' => $report->id, 'enrollment_id' => $enrollment->id]);
    }

    public function test_student_without_history_can_be_hard_deleted_and_cross_institution_access_is_denied(): void
    {
        [$institution, $admin] = $this->users();
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-001', 'full_name' => 'Siswa Hapus', 'gender' => 'L']);
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);
        $otherAdmin = User::factory()->withRole('ADMIN')->create(['institution_id' => $otherInstitution->id]);

        $this->actingAs($otherAdmin)->get(route('admin.students.show', $student))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.students.destroy', $student))->assertRedirect();
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    /**
     * @return array{0: Institution, 1: User, 2: User, 3: User}
     */
    private function users(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Siswa Uji']);
        $admin = User::factory()->withRole('ADMIN')->create(['institution_id' => $institution->id]);
        $guru = User::factory()->withRole('GURU')->create(['institution_id' => $institution->id]);
        $kepsek = User::factory()->withRole('KEPSEK')->create(['institution_id' => $institution->id]);

        return [$institution, $admin, $guru, $kepsek];
    }

    /**
     * @return array{0: AcademicClass, 1: AcademicYear}
     */
    private function classFor(Institution $institution, User $guru): array
    {
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'PA', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'homeroom_teacher_id' => $guru->id, 'code' => 'PA-1A', 'name' => 'Kelas 1A', 'status' => 'ACTIVE']);

        return [$class, $year];
    }
}
