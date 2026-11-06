<?php

namespace Tests\Unit\Services;

use App\Enums\RoleCode;
use App\Exceptions\GradeConcurrencyConflict;
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
use App\Services\GradeConcurrencyService;
use App\Services\PredicateRangeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class GradeConcurrencyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_page_is_rejected_and_preserves_the_newest_grade(): void
    {
        [$grade, $otherGrade, $assignment, $otherAssignment] = $this->academicSetup();
        $stalePage = $grade->fresh();
        $expectedUpdatedAt = $stalePage->updated_at->toIso8601String();

        Carbon::setTestNow(Carbon::parse($expectedUpdatedAt)->addSecond());
        $latest = app(GradeConcurrencyService::class)->update(
            $grade->fresh(),
            $expectedUpdatedAt,
            ['score' => 88, 'description' => 'Diisi oleh Guru Matematika.'],
        );
        Carbon::setTestNow();

        try {
            app(GradeConcurrencyService::class)->update(
                $stalePage,
                $expectedUpdatedAt,
                ['score' => 42, 'description' => 'Data lama dari halaman Guru Matematika.'],
            );
            $this->fail('Halaman nilai yang kedaluwarsa seharusnya ditolak.');
        } catch (GradeConcurrencyConflict $conflict) {
            $this->assertSame($latest->id, $conflict->grade()->id);
            $this->assertTrue($conflict->lastUpdatedAt()->equalTo($latest->updated_at));
            $this->assertSame(88, $conflict->payload()['last_score']);
            $this->assertSame($latest->updated_at->toIso8601String(), $conflict->payload()['last_updated_at']);
        }

        $persisted = $grade->fresh();
        $otherPersisted = $otherGrade->fresh();

        $this->assertSame(88, $persisted->score);
        $this->assertSame('Diisi oleh Guru Matematika.', $persisted->description);
        $this->assertSame($assignment->id, $persisted->subject_assignment_id);
        $this->assertSame(91, $otherPersisted->score);
        $this->assertSame($otherAssignment->id, $otherPersisted->subject_assignment_id);
    }

    public function test_fresh_updates_are_scoped_to_one_teacher_subject_grade(): void
    {
        [$grade, $otherGrade, $assignment, $otherAssignment] = $this->academicSetup();
        $expectedUpdatedAt = $grade->updated_at;

        $updated = app(GradeConcurrencyService::class)->updateScore($grade, $expectedUpdatedAt, 76);

        $this->assertSame(76, $updated->score);
        $this->assertSame($assignment->id, $updated->subject_assignment_id);
        $this->assertSame(91, $otherGrade->fresh()->score);
        $this->assertSame($otherAssignment->id, $otherGrade->fresh()->subject_assignment_id);
    }

    public function test_stale_page_cannot_reassign_a_grade_to_another_mapel(): void
    {
        [$grade, , , $otherAssignment] = $this->academicSetup();

        try {
            app(GradeConcurrencyService::class)->update(
                $grade,
                $grade->updated_at,
                ['score' => 55, 'subject_assignment_id' => $otherAssignment->id],
            );
            $this->fail('Subject assignment nilai seharusnya tidak dapat dipindahkan.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        $this->assertSame($grade->subject_assignment_id, $grade->fresh()->subject_assignment_id);
        $this->assertSame(85, $grade->fresh()->score);
    }

    /** @return array{0: ReportGrade, 1: ReportGrade, 2: SubjectAssignment, 3: SubjectAssignment} */
    private function academicSetup(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Concurrency Uji']);
        $guruA = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $guruB = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1]);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas 1 A', 'status' => 'ACTIVE']);
        $math = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK']);
        $science = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam', 'short_name' => 'IPA']);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $math->id, 'teacher_id' => $guruA->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
        $otherAssignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $science->id, 'teacher_id' => $guruB->id, 'minimum_score' => 70, 'display_order' => 2, 'active' => true]);
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-CONCURRENCY', 'full_name' => 'Siswa Concurrency']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);
        $reportCard = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'DRAFT']);

        app(PredicateRangeService::class)->ensureDefaults($institution);

        $grade = $reportCard->grades()->create([
            'subject_assignment_id' => $assignment->id,
            'subject_name_snapshot' => 'Matematika',
            'subject_short_name_snapshot' => 'MTK',
            'minimum_score_snapshot' => 70,
            'predicate_ranges_snapshot' => [],
            'display_order_snapshot' => 1,
            'score' => 85,
            'predicate' => 'B',
            'status' => 'DRAFT',
        ]);
        $otherGrade = $reportCard->grades()->create([
            'subject_assignment_id' => $otherAssignment->id,
            'subject_name_snapshot' => 'Ilmu Pengetahuan Alam',
            'subject_short_name_snapshot' => 'IPA',
            'minimum_score_snapshot' => 70,
            'predicate_ranges_snapshot' => [],
            'display_order_snapshot' => 2,
            'score' => 91,
            'predicate' => 'A',
            'status' => 'DRAFT',
        ]);

        return [$grade, $otherGrade, $assignment, $otherAssignment];
    }
}
