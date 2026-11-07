<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicDecision;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicDecisionService;
use App\Services\EnrollmentPlacementService;
use App\Services\ReportCardCompletenessService;
use App\Services\Reports\ReportCardPdfDataService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AcademicDecisionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_homeroom_can_save_and_revise_without_approval_and_changes_are_audited(): void
    {
        $f = $this->fixture();
        $this->actingAs($f['teacher'])->get(route('academic-decisions.index'))->assertOk()->assertSee('Simpan keputusan');
        $this->save($f, 'PROMOTE', 0)->assertRedirect();
        $decision = AcademicDecision::query()->firstOrFail();
        $this->assertSame('Naik ke tingkat 2', $decision->label_snapshot);
        $this->assertSame($f['teacher']->id, $decision->decided_by);
        $this->save($f, 'REPEAT', 1)->assertRedirect();
        $this->assertDatabaseHas('academic_decisions', ['id' => $decision->id, 'decision' => 'REPEAT', 'revision' => 2]);
        $audit = AuditLog::query()->where('action', 'academic_decision.saved')->latest('id')->firstOrFail();
        $this->assertSame('PROMOTE', $audit->old_value_json['decision']);
        $this->assertSame('REPEAT', $audit->new_value_json['decision']);
        $this->save($f, 'PROMOTE', 1)->assertSessionHasErrors('decision');
    }

    public function test_clearing_a_decision_retains_history_and_requires_a_new_revision(): void
    {
        $f = $this->fixture();
        $this->save($f, 'PROMOTE', 0)->assertRedirect();
        $this->save($f, '', 1)->assertRedirect();
        $this->assertNull(app(AcademicDecisionService::class)->forReport($f['report']->fresh()));
        $this->assertDatabaseHas('academic_decisions', ['student_id' => $f['student']->id, 'decision' => 'UNDECIDED', 'revision' => 2]);
        $this->save($f, 'REPEAT', 1)->assertSessionHasErrors('decision');
        $this->save($f, 'REPEAT', 2)->assertRedirect();
    }

    #[TestWith([6, 'Paket A'])]
    #[TestWith([9, 'Paket B'])]
    #[TestWith([12, 'Paket C'])]
    public function test_package_endings_use_graduation_instead_of_promotion(int $level, string $program): void
    {
        $f = $this->fixture($level);
        $this->save($f, 'PROMOTE', 0)->assertSessionHasErrors('decision');
        $this->save($f, 'GRADUATE', 0)->assertRedirect();
        $this->assertDatabaseHas('academic_decisions', ['student_id' => $f['student']->id, 'label_snapshot' => 'Lulus '.$program]);
        $this->save($f, 'NOT_GRADUATED', 1)->assertRedirect();
        $this->assertDatabaseHas('academic_decisions', ['student_id' => $f['student']->id, 'decision' => 'NOT_GRADUATED']);
    }

    public function test_subject_teacher_admin_and_kepsek_cannot_write_decisions(): void
    {
        $f = $this->fixture();
        $this->get(route('academic-decisions.index'))->assertRedirect(route('login'));
        foreach ([RoleCode::Guru, RoleCode::Admin, RoleCode::Kepsek] as $role) {
            $actor = User::factory()->withRole($role)->create(['institution_id' => $f['institution']->id]);
            $this->actingAs($actor)->put(route('academic-decisions.update', $f['class']), ['rows' => [$f['enrollment']->id => ['decision' => 'PROMOTE', 'revision' => 0]]])->assertForbidden();
        }
        $this->assertDatabaseCount('academic_decisions', 0);
    }

    public function test_cross_tenant_and_other_class_rows_are_rejected_atomically(): void
    {
        $f = $this->fixture();
        $other = $this->fixture(suffix: 'other');
        $this->actingAs($f['teacher'])->get(route('academic-decisions.index', ['class_id' => $other['class']->id]))->assertNotFound();
        $this->put(route('academic-decisions.update', $f['class']), ['rows' => [
            $f['enrollment']->id => ['decision' => 'PROMOTE', 'revision' => 0],
            $other['enrollment']->id => ['decision' => 'PROMOTE', 'revision' => 0],
        ]])->assertNotFound();
        $this->assertDatabaseCount('academic_decisions', 0);
    }

    public function test_ganjil_and_closed_year_are_read_only(): void
    {
        $f = $this->fixture();
        $f['semester']->update(['code' => 'GANJIL', 'sequence' => 1]);
        $this->actingAs($f['teacher'])->get(route('academic-decisions.index'))->assertOk()->assertSee('Tidak ada keputusan yang perlu diisi');
        $this->save($f, 'PROMOTE', 0)->assertSessionHasErrors('decision');
        $f['semester']->update(['code' => 'GENAP', 'sequence' => 2, 'status' => 'CLOSED']);
        $f['year']->update(['status' => 'CLOSED']);
        $this->save($f, 'PROMOTE', 0)->assertSessionHasErrors('decision');
    }

    public function test_transfer_invalidates_old_decision_until_new_homeroom_reconfirms(): void
    {
        $f = $this->fixture();
        $this->save($f, 'PROMOTE', 0)->assertRedirect();
        $newTeacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $f['institution']->id]);
        $target = AcademicClass::query()->create(['academic_year_id' => $f['year']->id, 'program_id' => $f['class']->program_id, 'level_id' => $f['class']->level_id,
            'name' => 'Kelas baru', 'code' => 'NEW', 'homeroom_teacher_id' => $newTeacher->id]);
        app(EnrollmentPlacementService::class)->transfer($f['enrollment'], $target, '2027-01-10', 'Pindah rombel', null, $f['admin'], $f['enrollment']->id);
        $new = Enrollment::query()->where('student_id', $f['student']->id)->where('enrollment_status', 'ACTIVE')->firstOrFail();
        $this->assertNull(app(AcademicDecisionService::class)->forReport($f['report']->fresh()));
        $this->actingAs($newTeacher)->put(route('academic-decisions.update', $target), ['rows' => [$new->id => ['decision' => 'REPEAT', 'revision' => 1]]])->assertRedirect();
        $this->assertSame('REPEAT', app(AcademicDecisionService::class)->forReport($f['report']->fresh())?->decision);
        $this->assertDatabaseCount('academic_decisions', 1);
        $this->assertSame(2, AuditLog::query()->where('action', 'academic_decision.saved')->count());
    }

    public function test_genap_report_requires_decision_and_only_full_report_prints_it(): void
    {
        $f = $this->fixture();
        $summary = app(ReportCardCompletenessService::class)->summary($f['report']);
        $this->assertContains('academic_decision', array_column($summary['missing'], 'key'));
        $this->save($f, 'PROMOTE', 0)->assertRedirect();
        $data = app(ReportCardPdfDataService::class)->data($f['report']->fresh(), false);
        $this->assertStringContainsString('Naik ke tingkat 2', view('reports.report-card.pdf', $data)->render());
        $this->assertStringNotContainsString('Keputusan Wali Kelas', view('reports.report-card.identity', $data)->render());
        $this->assertStringNotContainsString('Keputusan Wali Kelas', view('reports.report-card.grades', $data)->render());
        $f['year']->update(['status' => 'CLOSED']);
        $f['semester']->update(['status' => 'CLOSED']);
        AcademicDecision::query()->delete();
        $summary = app(ReportCardCompletenessService::class)->summary($f['report']->fresh());
        $this->assertNotContains('academic_decision', array_column($summary['missing'], 'key'));
    }

    public function test_repair_restores_sole_draft_parent_and_does_not_touch_reports(): void
    {
        $f = $this->fixture();
        $f['year']->update(['status' => 'DRAFT']);
        $before = $f['report']->fresh()->getAttributes();
        $this->artisan('academic-periods:repair', ['--institution' => $f['institution']->id])->assertSuccessful();
        $this->assertDatabaseHas('academic_years', ['id' => $f['year']->id, 'status' => 'DRAFT']);
        $this->artisan('academic-periods:repair', ['--institution' => $f['institution']->id, '--apply' => true])->assertSuccessful();
        $this->assertDatabaseHas('academic_years', ['id' => $f['year']->id, 'status' => 'ACTIVE']);
        $this->assertSame($before, $f['report']->fresh()->getAttributes());
        $this->assertDatabaseHas('audit_logs', ['action' => 'academic_period.consistency_repaired']);
    }

    public function test_repair_does_not_guess_when_another_year_is_active(): void
    {
        $f = $this->fixture();
        $f['year']->update(['status' => 'DRAFT']);
        AcademicYear::query()->create(['institution_id' => $f['institution']->id, 'name' => 'Autre', 'status' => 'ACTIVE']);
        $this->artisan('academic-periods:repair', ['--institution' => $f['institution']->id, '--apply' => true])->assertFailed();
        $this->assertDatabaseHas('academic_years', ['id' => $f['year']->id, 'status' => 'DRAFT']);
    }

    private function save(array $f, string $decision, int $revision): TestResponse
    {
        return $this->actingAs($f['teacher'])->put(route('academic-decisions.update', $f['class']), ['rows' => [$f['enrollment']->id => compact('decision', 'revision')]]);
    }

    private function fixture(int $level = 1, string $suffix = ''): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Uji'.$suffix]);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $semester = $year->semesters()->create(['code' => 'GENAP', 'name' => 'Semester Genap', 'sequence' => 2, 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'name' => $level <= 6 ? 'Paket A' : ($level <= 9 ? 'Paket B' : 'Paket C'), 'code' => 'PA']);
        $grade = Level::query()->create(['program_id' => $program->id, 'number' => $level, 'name' => (string) $level]);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $grade->id,
            'name' => 'Kelas '.$level, 'code' => 'K1', 'homeroom_teacher_id' => $teacher->id]);
        $student = Student::query()->create(['institution_id' => $institution->id, 'full_name' => 'Ahmad', 'student_number' => '001']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01']);
        $enrollment->update(['reporting_enrollment_id' => $enrollment->id]);
        $report = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'DRAFT']);

        return compact('institution', 'admin', 'teacher', 'year', 'semester', 'class', 'student', 'enrollment', 'report');
    }
}
