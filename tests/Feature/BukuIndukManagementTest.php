<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
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
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BukuIndukManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_and_kepsek_can_view_buku_induk_index_and_search(): void
    {
        [$institution, $admin, $guru, $kepsek] = $this->users();

        $student = Student::query()->create([
            'institution_id' => $institution->id,
            'student_number' => 'PD-001',
            'national_student_number' => '1234567890',
            'nik' => '3201010101010001',
            'full_name' => 'Warga Belajar Hebat',
            'gender' => 'L',
            'status' => 'ACTIVE',
        ]);

        // Admin can view
        $this->actingAs($admin)->get(route('admin.buku-induk.index'))
            ->assertOk()
            ->assertSee('Buku Induk Peserta Didik')
            ->assertSee('Warga Belajar Hebat')
            ->assertSee('PD-001')
            ->assertSee('3201010101010001');

        // Kepsek can view
        $this->actingAs($kepsek)->get(route('admin.buku-induk.index'))
            ->assertOk()
            ->assertSee('Warga Belajar Hebat');

        // Search by NIK
        $this->actingAs($admin)->get(route('admin.buku-induk.index', ['search' => '3201010101010001']))
            ->assertOk()
            ->assertSee('Warga Belajar Hebat');

        // Search with non-matching term
        $this->actingAs($admin)->get(route('admin.buku-induk.index', ['search' => 'TidakAda']))
            ->assertOk()
            ->assertDontSee('Warga Belajar Hebat');
    }

    public function test_admin_can_view_individual_buku_induk_sheet_with_academic_records(): void
    {
        [$institution, $admin, $guru] = $this->users();

        $student = Student::query()->create([
            'institution_id' => $institution->id,
            'student_number' => 'PD-002',
            'national_student_number' => '0098765432',
            'nik' => '3201020202020002',
            'family_card_number' => '3201020202029999',
            'full_name' => 'Ahmad Santoso',
            'nickname' => 'Ahmad',
            'gender' => 'L',
            'birth_place' => 'Jakarta',
            'birth_date' => '2008-05-15',
            'religion' => 'Islam',
            'citizenship' => 'WNI',
            'birth_order' => 1,
            'siblings_count' => 2,
            'address' => 'Jl. Pendidikan No. 123',
            'phone_number' => '081234567890',
            'father_name' => 'Bambang Santoso',
            'father_occupation' => 'Wiraswasta',
            'mother_name' => 'Siti Aminah',
            'mother_occupation' => 'Ibu Rumah Tangga',
            'previous_school_name' => 'SD Negeri 1 Merdeka',
            'previous_diploma_number' => 'DN-01/D-SD/06/0012345',
            'entry_date' => '2023-07-15',
            'status' => 'ACTIVE',
        ]);

        [$class, $year, $semester] = $this->createClassWithReport($institution, $guru, $student);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk.show', $student));
        $response->assertOk()
            ->assertSee('Ahmad Santoso')
            ->assertSee('Bambang Santoso')
            ->assertSee('Siti Aminah')
            ->assertSee('SD Negeri 1 Merdeka')
            ->assertSee('DN-01/D-SD/06/0012345')
            ->assertSee('3201020202020002')
            ->assertSee('3201020202029999');
    }

    public function test_admin_can_export_buku_induk_register_to_excel(): void
    {
        [$institution, $admin] = $this->users();

        Student::query()->create([
            'institution_id' => $institution->id,
            'student_number' => 'PD-003',
            'full_name' => 'Siti Nurhaliza',
            'gender' => 'P',
            'nik' => '3201030303030003',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk.export-excel'));
        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_can_print_and_generate_sheet_pdf(): void
    {
        [$institution, $admin] = $this->users();

        $student = Student::query()->create([
            'institution_id' => $institution->id,
            'student_number' => 'PD-004',
            'full_name' => 'Budi Pratama',
            'gender' => 'L',
            'status' => 'ACTIVE',
        ]);

        // Browser print view
        $this->actingAs($admin)->get(route('admin.buku-induk.sheet-print', $student))
            ->assertOk()
            ->assertSee('LEMBAR BUKU INDUK PESERTA DIDIK')
            ->assertSee('Budi Pratama');

        // PDF route (falls back to HTML view if Chrome/PdfEngine unavailable)
        $this->actingAs($admin)->get(route('admin.buku-induk.sheet-pdf', $student))
            ->assertOk();
    }

    public function test_admin_can_store_and_update_student_with_all_official_buku_induk_fields(): void
    {
        [$institution, $admin] = $this->users();

        $createResponse = $this->actingAs($admin)->post(route('admin.students.store'), [
            'student_number' => 'PD-010',
            'national_student_number' => '1234567891',
            'nik' => '3201012345670001',
            'family_card_number' => '3201012345679999',
            'full_name' => 'Dewi Lestari',
            'nickname' => 'Dewi',
            'gender' => 'P',
            'birth_place' => 'Surabaya',
            'birth_date' => '2007-08-20',
            'religion' => 'Islam',
            'citizenship' => 'WNI',
            'birth_order' => 2,
            'siblings_count' => 3,
            'address' => 'Jl. Mawar No. 45',
            'postal_code' => '60111',
            'phone_number' => '085678901234',
            'living_with' => 'Orang Tua',
            'transportation_mode' => 'Sepeda Motor',
            'father_name' => 'Supriyadi',
            'father_nik' => '3201012345670002',
            'father_occupation' => 'PNS',
            'mother_name' => 'Endang',
            'mother_nik' => '3201012345670003',
            'mother_occupation' => 'Guru',
            'previous_school_name' => 'SMP Negeri 2 Surabaya',
            'previous_diploma_number' => 'DN-02/D-SMP/06/0098765',
            'entry_date' => '2023-07-10',
            'status' => 'ACTIVE',
        ]);

        $createResponse->assertRedirect();

        $student = Student::query()->where('student_number', 'PD-010')->first();
        $this->assertNotNull($student);
        $this->assertSame('3201012345670001', $student->nik);
        $this->assertSame('Dewi Lestari', $student->full_name);
        $this->assertSame('Supriyadi', $student->father_name);
        $this->assertSame('SMP Negeri 2 Surabaya', $student->previous_school_name);

        // Update
        $this->actingAs($admin)->put(route('admin.students.update', $student), [
            'student_number' => 'PD-010',
            'full_name' => 'Dewi Lestari M.Pd',
            'gender' => 'P',
            'status' => 'GRADUATED',
            'graduation_certificate_number' => 'IJZ-PKBM-2026-001',
        ])->assertRedirect();

        $student->refresh();
        $this->assertSame('Dewi Lestari M.Pd', $student->full_name);
        $this->assertSame('GRADUATED', $student->status);
        $this->assertSame('IJZ-PKBM-2026-001', $student->graduation_certificate_number);
    }

    public function test_cross_institution_access_is_forbidden(): void
    {
        [$institution, $admin] = $this->users();
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);
        $otherAdmin = User::factory()->withRole('ADMIN')->create(['institution_id' => $otherInstitution->id]);

        $student = Student::query()->create([
            'institution_id' => $institution->id,
            'student_number' => 'PD-999',
            'full_name' => 'Siswa Rahasia',
            'gender' => 'L',
        ]);

        $this->actingAs($otherAdmin)->get(route('admin.buku-induk.show', $student))
            ->assertForbidden();

        $this->actingAs($otherAdmin)->get(route('admin.buku-induk.sheet-pdf', $student))
            ->assertForbidden();
    }

    /**
     * @return array{0: Institution, 1: User, 2: User, 3: User}
     */
    private function users(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Harapan Bangsa', 'npsn' => 'P1234567', 'permit_number' => '421/01/DP/2020']);
        $admin = User::factory()->withRole('ADMIN')->create(['institution_id' => $institution->id]);
        $guru = User::factory()->withRole('GURU')->create(['institution_id' => $institution->id]);
        $kepsek = User::factory()->withRole('KEPSEK')->create(['institution_id' => $institution->id]);

        return [$institution, $admin, $guru, $kepsek];
    }

    /**
     * @return array{0: AcademicClass, 1: AcademicYear, 2: Semester}
     */
    private function createClassWithReport(Institution $institution, User $guru, Student $student): array
    {
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2025/2026', 'status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1]);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'PB', 'name' => 'Paket B']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 7, 'name' => 'Tingkat 7']);
        $class = AcademicClass::query()->create([
            'academic_year_id' => $year->id,
            'program_id' => $program->id,
            'level_id' => $level->id,
            'homeroom_teacher_id' => $guru->id,
            'code' => 'PB-7A',
            'name' => 'Kelas 7A',
            'status' => 'ACTIVE',
        ]);

        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
            'start_date' => '2025-07-15',
            'enrollment_status' => 'ACTIVE',
        ]);

        $subject = Subject::query()->create([
            'institution_id' => $institution->id,
            'code' => 'BINDO',
            'name' => 'Bahasa Indonesia',
            'short_name' => 'B. Indo',
        ]);

        $assignment = SubjectAssignment::query()->create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $guru->id,
            'kkm' => 75,
        ]);

        $reportCard = ReportCard::query()->create([
            'enrollment_id' => $enrollment->id,
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'status' => 'LOCKED',
            'locked_at' => now(),
        ]);

        ReportGrade::query()->create([
            'report_card_id' => $reportCard->id,
            'subject_assignment_id' => $assignment->id,
            'score' => 88,
            'predicate_snapshot' => 'B',
            'minimum_score_snapshot' => 75,
            'subject_name_snapshot' => 'Bahasa Indonesia',
            'subject_code_snapshot' => 'BINDO',
            'subject_short_name_snapshot' => 'B. Indo',
            'display_order_snapshot' => 1,
        ]);

        return [$class, $year, $semester];
    }
}
