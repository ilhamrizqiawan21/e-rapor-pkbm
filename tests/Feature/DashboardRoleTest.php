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
use App\Models\ReportGrade;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardRoleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_dashboard_aggregates_period_metrics_and_statuses(): void
    {
        [$institution, $year, $semester, $class, $assignment] = $this->academicSetup();
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $first = $this->enrollment($institution, $class, 'PD-001', 'Siswa Pertama', $year);
        $second = $this->enrollment($institution, $class, 'PD-002', 'Siswa Kedua', $year);
        $firstCard = ReportCard::query()->create(['enrollment_id' => $first->id, 'semester_id' => $semester->id, 'status' => 'IN_REVIEW']);
        $secondCard = ReportCard::query()->create(['enrollment_id' => $second->id, 'semester_id' => $semester->id, 'status' => 'APPROVED']);
        ReportGrade::query()->create($this->gradeData($firstCard, $assignment, 80));

        $this->actingAs($admin)->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee('Kelas aktif')
            ->assertSee('Kelas 1 A')
            ->assertSee('1 / 2')
            ->assertSee('Siap dicetak');
    }

    public function test_guru_dashboard_only_exposes_their_assignments_and_progress(): void
    {
        [$institution, $year, $semester, $class, $assignment] = $this->academicSetup();
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $otherGuru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $assignment->update(['teacher_id' => $guru->id]);
        $otherSubject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'IPS', 'name' => 'Ilmu Sosial', 'short_name' => 'IPS', 'active' => true]);
        SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $otherSubject->id, 'teacher_id' => $otherGuru->id, 'minimum_score' => 70, 'display_order' => 2, 'active' => true]);
        $enrollment = $this->enrollment($institution, $class, 'PD-001', 'Siswa Guru', $year);
        $card = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'DRAFT']);
        ReportGrade::query()->create($this->gradeData($card, $assignment, 88));

        $this->actingAs($guru)->get(route('dashboard.guru'))
            ->assertOk()
            ->assertSee('Dashboard Guru')
            ->assertSee('Matematika')
            ->assertSee('100%')
            ->assertDontSee('Ilmu Sosial')
            ->assertSee('Buka bank deskripsi');
    }

    public function test_guru_sidebar_focuses_on_teaching_workflow(): void
    {
        $guru = User::factory()->withRole(RoleCode::Guru)->create([
            'institution_id' => Institution::query()->create(['name' => 'PKBM Menu Guru'])->id,
        ]);

        $this->actingAs($guru)->get(route('dashboard.guru'))
            ->assertOk()
            ->assertSee('Fokus pada penilaian, kelas, dan rapor yang menjadi tanggung jawab Anda.')
            ->assertSee('Input nilai')
            ->assertSee('Bank deskripsi')
            ->assertSee('Kelengkapan rapor')
            ->assertSee('Generator leger')
            ->assertDontSee('Data Guru')
            ->assertDontSee('Program & tingkat')
            ->assertDontSee('Pengaturan PKBM')
            ->assertDontSee('Backup database');
    }

    public function test_kepsek_dashboard_shows_review_queue_class_summary_and_actions(): void
    {
        [$institution, $year, $semester, $class, $assignment] = $this->academicSetup();
        $kepsek = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $institution->id]);
        $enrollment = $this->enrollment($institution, $class, 'PD-001', 'Siswa Review', $year);
        $card = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'IN_REVIEW']);
        ReportGrade::query()->create($this->gradeData($card, $assignment, 91));

        $this->actingAs($kepsek)->get(route('dashboard.kepsek'))
            ->assertOk()
            ->assertSee('Dashboard Kepala PKBM')
            ->assertSee('Siswa Review')
            ->assertSee('Buka rapor')
            ->assertSee('Kesiapan per kelas')
            ->assertSee('Status penerbitan')
            ->assertSee('tanpa tahap pengajuan atau persetujuan')
            ->assertDontSee('Input nilai')
            ->assertDontSee('Bank deskripsi')
            ->assertDontSee('Pengaturan PKBM');
    }

    public function test_dashboard_tolerates_missing_active_period(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Tanpa Periode']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);

        $this->actingAs($admin)->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('Langkah berikutnya: Aktifkan periode')
            ->assertSee('Aktifkan periode untuk melihat progres nilai per kelas.');
    }

    /** @return array{0: Institution, 1: AcademicYear, 2: Semester, 3: AcademicClass, 4: SubjectAssignment} */
    private function academicSetup(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Dashboard']);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1, 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas 1 A', 'status' => 'ACTIVE']);
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $subject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK', 'active' => true]);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);

        return [$institution, $year, $semester, $class, $assignment];
    }

    private function enrollment(Institution $institution, AcademicClass $class, string $number, string $name, AcademicYear $year): Enrollment
    {
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => $number, 'full_name' => $name, 'status' => 'ACTIVE']);

        return Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);
    }

    /** @return array<string, mixed> */
    private function gradeData(ReportCard $card, SubjectAssignment $assignment, int $score): array
    {
        return ['report_card_id' => $card->id, 'subject_assignment_id' => $assignment->id, 'subject_name_snapshot' => 'Matematika', 'subject_short_name_snapshot' => 'MTK', 'minimum_score_snapshot' => 70, 'display_order_snapshot' => 1, 'score' => $score, 'predicate' => 'B', 'description' => 'Capaian baik.', 'status' => 'DRAFT'];
    }
}
