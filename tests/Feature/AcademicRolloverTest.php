<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicDecision;
use App\Models\AcademicYear;
use App\Models\AttendanceSummary;
use App\Models\AttitudeAssessment;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Level;
use App\Models\PeriodPreparation;
use App\Models\Program;
use App\Models\ReportCard;
use App\Models\ReportGrade;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\AcademicRolloverService;
use App\Services\PeriodReadinessService;
use App\Services\Reports\ReportCardPdfDataService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class AcademicRolloverTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_year_requires_review_and_preserves_reports_while_creating_empty_placements(): void
    {
        $f = $this->fixture();
        $this->assign($f, 'PROMOTE');
        $this->finalize($f);
        $this->prepare($f);
        $this->place($f);
        $this->actingAs($f['admin'])->post(route('admin.academic-years.transition.store', $f['source']), ['fingerprint' => str_repeat('a', 64), 'confirm' => 1])->assertSessionHasErrors('transition');
        $state = $this->review($f);
        $this->assertDatabaseCount('enrollments', 1);
        $this->activate($f, $state)->assertRedirect(route('admin.academic-years.show', $f['target']));
        $this->assertDatabaseHas('enrollments', ['id' => $f['enrollment']->id, 'enrollment_status' => 'COMPLETED']);
        $new = Enrollment::query()->where('academic_year_id', $f['target']->id)->firstOrFail();
        $this->assertSame($new->id, $new->reporting_enrollment_id);
        $this->assertSame(0, $new->reportCards()->count());
        $this->assertDatabaseHas('report_grades', ['report_card_id' => $f['report']->id, 'score' => 90]);
        $f['student']->update(['full_name' => 'Nama berubah']);
        $archived = app(ReportCardPdfDataService::class)->data($f['report']->fresh());
        $this->assertSame('Ahmad', $archived['studentName']);
        $this->assertSame('Naik ke tingkat 2', $archived['decisionLabel']);
        $this->assertFalse($f['admin']->can('manageCompleteness', $f['report']->fresh()));
        $this->assertTrue($f['admin']->can('export', $f['report']->fresh()));
        $this->assertDatabaseHas('audit_logs', ['action' => 'academic_period.transitioned']);
        $this->activate($f, $state)->assertSessionHasErrors('transition');
        $this->assertDatabaseCount('enrollments', 2);
    }

    public function test_semester_transition_keeps_students_and_automatically_creates_genap(): void
    {
        $f = $this->fixture(false);
        $state = app(PeriodReadinessService::class)->evaluate($f['source']);
        $this->assertTrue($state['ready']);
        $this->actingAs($f['admin'])->get(route('admin.academic-years.show', $f['source']))->assertOk()->assertSee('Mulai semester genap');
        $this->activate($f, $state)->assertRedirect();
        $this->assertDatabaseHas('semesters', ['id' => $f['semester']->id, 'status' => 'CLOSED']);
        $this->assertDatabaseHas('semesters', ['academic_year_id' => $f['source']->id, 'code' => 'GENAP', 'status' => 'ACTIVE']);
        $this->assertDatabaseHas('academic_years', ['id' => $f['source']->id, 'status' => 'ACTIVE']);
        $this->assertDatabaseHas('enrollments', ['id' => $f['enrollment']->id, 'enrollment_status' => 'ACTIVE']);
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_invalid_level_duplicate_placement_and_stale_review_are_rejected(): void
    {
        $f = $this->fixture();
        $this->assign($f, 'PROMOTE');
        $this->prepare($f);
        $this->place($f);
        $state = $this->review($f);
        $this->assign($f, 'REPEAT');
        $this->activate($f, $state)->assertSessionHasErrors('transition');
        $state = $this->review($f);
        $this->assertFalse($state['ready']);
        $this->assertSame(1, $state['counts']['missing_destinations']);
        $this->assign($f, 'PROMOTE');
        $this->finalize($f);
        Enrollment::query()->create(['student_id' => $f['student']->id, 'academic_year_id' => $f['target']->id, 'class_id' => $f['destination']->id, 'start_date' => '2027-07-01']);
        $state = $this->review($f);
        $this->assertFalse($state['ready']);
        $this->activate($f, $state)->assertSessionHasErrors('transition');
        $this->assertDatabaseHas('academic_years', ['id' => $f['source']->id, 'status' => 'ACTIVE']);
    }

    public function test_copy_is_repeatable_and_does_not_copy_students_or_grades(): void
    {
        $f = $this->fixture();
        $this->prepare($f);
        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($f['admin'])->post(route('admin.academic-years.transition.copy', $f['source']))->assertRedirect();
        }
        $copy = $f['target']->classes()->where('code', 'K1')->firstOrFail();
        $this->assertSame(1, $copy->subjectAssignments()->count());
        $this->assertSame(0, $copy->enrollments()->count());
        $this->assertSame($f['teacher']->id, $copy->homeroom_teacher_id);
        $this->assertSame(70, $copy->subjectAssignments()->first()->minimum_score);
        $this->assertSame(2, $f['target']->classes()->count());
    }

    public function test_graduation_creates_no_target_placement_when_admin_chooses_finish(): void
    {
        $f = $this->fixture(level: 6);
        $this->assign($f, 'GRADUATE');
        $this->finalize($f);
        $this->prepare($f);
        $this->place($f, 'FINISH', false);
        $state = $this->review($f);
        $this->assertTrue($state['ready']);
        $this->activate($f, $state)->assertRedirect();
        $this->assertDatabaseHas('students', ['id' => $f['student']->id, 'status' => 'GRADUATED']);
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_repeat_and_not_graduated_require_same_level_destination(): void
    {
        foreach ([1 => 'REPEAT', 6 => 'NOT_GRADUATED'] as $level => $code) {
            $f = $this->fixture(level: $level, suffix: (string) $level);
            $f['destination']->update(['level_id' => $f['class']->level_id]);
            $this->assign($f, $code);
            $this->finalize($f);
            $this->prepare($f);
            $this->place($f);
            $state = $this->review($f);
            $this->assertTrue($state['ready']);
            $this->activate($f, $state)->assertRedirect();
            $this->assertDatabaseHas('enrollments', ['student_id' => $f['student']->id, 'academic_year_id' => $f['target']->id, 'class_id' => $f['destination']->id, 'enrollment_status' => 'ACTIVE']);
        }
    }

    public function test_cross_package_continuation_requires_next_level_and_explicit_choice(): void
    {
        $f = $this->fixture(level: 9);
        $this->assign($f, 'GRADUATE');
        $this->finalize($f);
        $this->prepare($f);
        $this->place($f);
        $state = $this->review($f);
        $this->assertFalse($state['ready']);
        $this->place($f, 'CONTINUE');
        $state = $this->review($f);
        $this->assertTrue($state['ready']);
        $this->activate($f, $state)->assertRedirect();
        $this->assertDatabaseHas('enrollments', ['student_id' => $f['student']->id, 'academic_year_id' => $f['target']->id, 'class_id' => $f['destination']->id]);
        $this->assertDatabaseHas('academic_decisions', ['student_id' => $f['student']->id, 'decision' => 'GRADUATE']);
        $this->assertDatabaseHas('students', ['id' => $f['student']->id, 'status' => 'ACTIVE']);
    }

    public function test_used_target_semester_and_another_tenants_destination_are_rejected(): void
    {
        $f = $this->fixture();
        $this->assign($f, 'PROMOTE');
        $this->finalize($f);
        $this->prepare($f);
        $other = $this->fixture(suffix: 'other');
        $prep = PeriodPreparation::query()->where('source_year_id', $f['source']->id)->firstOrFail();
        $this->actingAs($f['admin'])->put(route('admin.academic-years.transition.placements', $f['source']), [
            'revision' => $prep->revision, 'rows' => [$f['enrollment']->id => ['class_id' => $other['destination']->id]],
        ])->assertNotFound();
        $this->place($f);
        ReportCard::query()->create(['enrollment_id' => $f['enrollment']->id, 'semester_id' => $prep->target_semester_id]);
        $state = $this->review($f);
        $this->assertFalse($state['ready']);
        $this->activate($f, $state)->assertSessionHasErrors('transition');
    }

    public function test_role_tenant_and_decision_tampering_boundaries(): void
    {
        $f = $this->fixture();
        $this->get(route('admin.academic-years.transition', $f['source']))->assertRedirect(route('login'));
        $this->actingAs($f['teacher'])->get(route('admin.academic-years.transition', $f['source']))->assertForbidden();
        $other = $this->fixture(suffix: 'other');
        $this->actingAs($other['admin'])->get(route('admin.academic-years.transition', $f['source']))->assertNotFound();
        $this->prepare($f);
        $prep = PeriodPreparation::query()->firstWhere('source_year_id', $f['source']->id);
        $this->actingAs($f['admin'])->put(route('admin.academic-years.transition.placements', $f['source']), [
            'revision' => $prep->revision, 'rows' => [$f['enrollment']->id => ['decision' => 'PROMOTE']],
        ])->assertSessionHasErrors('rows.'.$f['enrollment']->id.'.decision');
        $this->assertDatabaseCount('academic_decisions', 0);
        $this->assertDatabaseCount('enrollments', 2);
    }

    public function test_incomplete_reports_block_both_transitions_and_no_confirmation_bypasses_them(): void
    {
        $f = $this->fixture(false);
        $f['report']->update(['document_number' => null]);
        $state = app(PeriodReadinessService::class)->evaluate($f['source']);
        $this->activate($f, $state)->assertSessionHasErrors('transition');
        $this->assertSame(1, $state['counts']['missing_reports']);
        $this->assertDatabaseHas('semesters', ['id' => $f['semester']->id, 'status' => 'ACTIVE']);
        $g = $this->fixture(suffix: 'genap');
        $this->prepare($g);
        $state = $this->review($g);
        $this->assertSame(1, $state['counts']['missing_decisions']);
        $this->activate($g, $state)->assertSessionHasErrors('transition');
    }

    public function test_draft_survives_new_session_and_kepsek_can_only_monitor(): void
    {
        $f = $this->fixture();
        $this->assign($f, 'PROMOTE');
        $this->prepare($f);
        $this->place($f);
        $this->flushSession();
        $response = $this->actingAs($f['admin'])->get(route('admin.academic-years.transition', [$f['source'], 'step' => 2]))->assertOk();
        $this->assertSame($f['destination']->id, $response->viewData('state')['rows'][0]['placement']->class_id);
        $head = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $f['institution']->id]);
        $this->actingAs($head)->get(route('admin.academic-years.transition', [$f['source'], 'step' => 3]))->assertOk()->assertDontSee('name="confirm"', false);
        $this->post(route('admin.academic-years.transition.preview', $f['source']))->assertForbidden();
    }

    public function test_failure_at_audit_rolls_back_archival_and_enrollments(): void
    {
        $f = $this->fixture();
        $this->assign($f, 'PROMOTE');
        $this->finalize($f);
        $this->prepare($f);
        $this->place($f);
        $state = $this->review($f);
        Event::listen('eloquent.creating: App\\Models\\AuditLog', function ($audit): void {
            if ($audit->action === 'academic_period.transitioned') {
                throw new RuntimeException('Simulated storage failure');
            }
        });
        try {
            app(AcademicRolloverService::class)->activate($f['admin'], $f['source'], $state['fingerprint']);
            $this->fail('Activation should roll back.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated storage failure', $exception->getMessage());
        } finally {
            Event::forget('eloquent.creating: App\\Models\\AuditLog');
        }
        $this->assertDatabaseHas('academic_years', ['id' => $f['source']->id, 'status' => 'ACTIVE']);
        $this->assertDatabaseCount('enrollments', 1);
        $this->assertNull($f['report']->fresh()->getAttribute('identity_snapshot'));
    }

    public function test_manual_edit_cannot_close_or_demote_active_year_or_semester(): void
    {
        $f = $this->fixture(false);
        $this->actingAs($f['admin'])->put(route('admin.academic-years.update', $f['source']), [
            'name' => $f['source']->name, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'DRAFT',
        ])->assertSessionHasErrors('status');
        $this->put(route('admin.academic-years.semesters.update', [$f['source'], $f['semester']]), [
            'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1, 'status' => 'CLOSED', 'confirm_closure' => 1,
        ])->assertSessionHasErrors('status');
        $this->assertDatabaseHas('academic_years', ['id' => $f['source']->id, 'status' => 'ACTIVE']);
    }

    private function assign(array $f, string $code): void
    {
        $revision = AcademicDecision::query()->where('student_id', $f['student']->id)->value('revision') ?? 0;
        $this->actingAs($f['teacher'])->put(route('academic-decisions.update', $f['class']), ['rows' => [
            $f['enrollment']->id => ['decision' => $code, 'revision' => $revision],
        ]])->assertRedirect();
    }

    private function finalize(array $f): void
    {
        $this->actingAs($f['teacher'])->post(route('academic-decisions.finalize', $f['class']))->assertRedirect();
    }

    private function prepare(array $f): void
    {
        $revision = PeriodPreparation::query()->where('source_year_id', $f['source']->id)->value('revision') ?? 0;
        $this->actingAs($f['admin'])->post(route('admin.academic-years.transition.prepare', $f['source']), [
            'name' => '2027/2028', 'start_date' => '2027-07-01', 'end_date' => '2028-06-30', 'revision' => $revision,
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    private function place(array $f, ?string $continuation = null, bool $withClass = true): void
    {
        $prep = PeriodPreparation::query()->where('source_year_id', $f['source']->id)->firstOrFail();
        $this->actingAs($f['admin'])->put(route('admin.academic-years.transition.placements', $f['source']), [
            'revision' => $prep->revision, 'configuration_reviewed' => 1,
            'rows' => [$f['enrollment']->id => ['class_id' => $withClass ? $f['destination']->id : null, 'continuation' => $continuation]],
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    private function review(array $f): array
    {
        $this->actingAs($f['admin'])->post(route('admin.academic-years.transition.preview', $f['source']))->assertRedirect()->assertSessionHasNoErrors();

        return $this->get(route('admin.academic-years.transition', [$f['source'], 'step' => 3]))->assertOk()->viewData('state');
    }

    private function activate(array $f, array $state): TestResponse
    {
        return $this->actingAs($f['admin'])->post(route('admin.academic-years.transition.store', $f['source']), ['fingerprint' => $state['fingerprint'], 'confirm' => 1]);
    }

    private function fixture(bool $genap = true, int $level = 1, string $suffix = ''): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Uji'.$suffix]);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $source = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        $target = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2027/2028', 'status' => 'DRAFT', 'start_date' => '2027-07-01', 'end_date' => '2028-06-30']);
        $semester = $source->semesters()->create(['code' => $genap ? 'GENAP' : 'GANJIL', 'name' => $genap ? 'Semester Genap' : 'Semester Ganjil', 'sequence' => $genap ? 2 : 1, 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'name' => $level <= 6 ? 'Paket A' : ($level <= 9 ? 'Paket B' : 'Paket C'), 'code' => 'PA']);
        $first = Level::query()->create(['program_id' => $program->id, 'number' => $level, 'name' => (string) $level]);
        $second = Level::query()->create(['program_id' => $program->id, 'number' => $level + 1, 'name' => (string) ($level + 1)]);
        $class = AcademicClass::query()->create(['academic_year_id' => $source->id, 'program_id' => $program->id, 'level_id' => $first->id, 'code' => 'K1', 'name' => 'Kelas '.$level, 'homeroom_teacher_id' => $teacher->id]);
        $destination = AcademicClass::query()->create(['academic_year_id' => $target->id, 'program_id' => $program->id, 'level_id' => $second->id, 'code' => 'K2', 'name' => 'Kelas '.($level + 1)]);
        $student = Student::query()->create(['institution_id' => $institution->id, 'full_name' => 'Ahmad', 'student_number' => '001']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $source->id, 'start_date' => '2026-07-01']);
        $enrollment->update(['reporting_enrollment_id' => $enrollment->id]);
        $subject = Subject::query()->create(['institution_id' => $institution->id, 'name' => 'Matematika', 'short_name' => 'MTK', 'code' => 'MAT']);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
        $report = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'document_number' => 'ARSIP-001', 'status' => 'DRAFT']);
        foreach (['SPIRITUAL', 'SOCIAL'] as $type) {
            AttitudeAssessment::query()->create(['report_card_id' => $report->id, 'attitude_type' => $type, 'description' => 'Baik']);
        }
        AttendanceSummary::query()->create(['report_card_id' => $report->id, 'sick_days' => 0, 'leave_days' => 0, 'unexcused_days' => 0]);
        ReportGrade::query()->create(['report_card_id' => $report->id, 'subject_assignment_id' => $assignment->id,
            'subject_name_snapshot' => 'Matematika', 'subject_short_name_snapshot' => 'MTK', 'minimum_score_snapshot' => 70, 'display_order_snapshot' => 1, 'score' => 90, 'predicate' => 'A', 'description' => 'Sangat baik']);

        return compact('institution', 'admin', 'teacher', 'source', 'target', 'semester', 'class', 'destination', 'student', 'enrollment', 'report');
    }
}
