<?php

namespace Tests\Unit\Services;

use App\Enums\DescriptionSnippetVisibility;
use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\DescriptionSnippet;
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
use App\Services\DescriptionSnippetUsageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class DescriptionSnippetUsageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_active_snippets_allowed_for_the_actor_and_subject(): void
    {
        [$admin, $guru, $subject, $otherSubject, $grade] = $this->academicSetup();
        $ownedPrivate = $this->snippet($admin, $subject, ['title' => 'Milik Admin']);
        $shared = $this->snippet($guru, $subject, ['title' => 'Shared', 'visibility' => DescriptionSnippetVisibility::Shared->value]);
        $generic = $this->snippet($guru, null, ['title' => 'Generic', 'visibility' => DescriptionSnippetVisibility::Shared->value]);
        $otherSubjectSnippet = $this->snippet($guru, $otherSubject, ['title' => 'Mapel Lain', 'visibility' => DescriptionSnippetVisibility::Shared->value]);
        $inactive = $this->snippet($admin, $subject, ['title' => 'Nonaktif', 'active' => false]);
        $otherPrivate = $this->snippet($guru, $subject, ['title' => 'Privat Guru']);

        $result = app(DescriptionSnippetUsageService::class)->listUsableSnippets($admin, $subject);

        $this->assertSame(collect([$ownedPrivate->id, $generic->id, $shared->id])->sort()->values()->all(), $result->pluck('id')->sort()->values()->all());
        $this->assertNotContains($otherSubjectSnippet->id, $result->pluck('id')->all());
        $this->assertNotContains($inactive->id, $result->pluck('id')->all());
        $this->assertNotContains($otherPrivate->id, $result->pluck('id')->all());
        $this->assertTrue($grade->exists);
    }

    public function test_it_applies_snippet_as_final_text_and_keeps_traceability(): void
    {
        [$admin, , $subject, , $grade] = $this->academicSetup();
        $snippet = $this->snippet($admin, $subject, ['content' => 'Menunjukkan kemajuan yang baik.']);

        $updated = app(DescriptionSnippetUsageService::class)->applySnippet($admin, $grade, $snippet);

        $this->assertSame($snippet->id, $updated->description_snippet_id);
        $this->assertSame('Menunjukkan kemajuan yang baik.', $updated->description);
        $this->assertSame('Menunjukkan kemajuan yang baik.', $snippet->fresh()->content);
        $this->assertTrue($updated->descriptionSnippet->is($snippet));
    }

    public function test_editing_final_text_does_not_mutate_the_original_snippet(): void
    {
        [$admin, , $subject, , $grade] = $this->academicSetup();
        $snippet = $this->snippet($admin, $subject, ['content' => 'Teks awal.']);
        $service = app(DescriptionSnippetUsageService::class);
        $service->applySnippet($admin, $grade, $snippet);

        $updated = $service->updateFinalDescription($admin, $grade, 'Teks final siswa yang diedit.');

        $this->assertSame($snippet->id, $updated->description_snippet_id);
        $this->assertSame('Teks final siswa yang diedit.', $updated->description);
        $this->assertSame('Teks awal.', $snippet->fresh()->content);
    }

    public function test_it_saves_final_description_as_a_new_private_snippet(): void
    {
        [$admin, , $subject, , $grade] = $this->academicSetup();
        $grade->update(['description' => 'Deskripsi khusus siswa.']);

        $snippet = app(DescriptionSnippetUsageService::class)->saveAsSnippet($admin, $grade, 'Deskripsi khusus');

        $this->assertNotSame($grade->description_snippet_id, $snippet->id);
        $this->assertSame($admin->id, $snippet->owner_user_id);
        $this->assertSame($subject->id, $snippet->subject_id);
        $this->assertSame('Deskripsi khusus siswa.', $snippet->content);
        $this->assertSame(DescriptionSnippetVisibility::Private, $snippet->visibility);
    }

    public function test_private_usage_and_shared_creation_are_authorized(): void
    {
        [$admin, $guru, $subject, , $grade] = $this->academicSetup();
        $private = $this->snippet($admin, $subject, ['title' => 'Privat Admin']);
        $service = app(DescriptionSnippetUsageService::class);

        $this->expectException(AuthorizationException::class);
        $service->applySnippet($guru, $grade, $private);
    }

    public function test_guru_can_apply_a_shared_snippet_and_save_a_private_one(): void
    {
        [$admin, $guru, $subject, , $grade] = $this->academicSetup();
        $shared = $this->snippet($admin, $subject, [
            'title' => 'Shared Admin',
            'visibility' => DescriptionSnippetVisibility::Shared->value,
        ]);
        $service = app(DescriptionSnippetUsageService::class);

        $updated = $service->applySnippet($guru, $grade, $shared);
        $private = $service->saveAsSnippet($guru, $grade, 'Salinan Guru', DescriptionSnippetVisibility::Private);

        $this->assertSame($shared->id, $updated->description_snippet_id);
        $this->assertSame($guru->id, $private->owner_user_id);
        $this->assertSame(DescriptionSnippetVisibility::Private, $private->visibility);
    }

    public function test_locked_report_rejects_apply_edit_and_save(): void
    {
        [$admin, , $subject, , $grade] = $this->academicSetup();
        $snippet = $this->snippet($admin, $subject);
        $grade->reportCard->update(['status' => 'LOCKED', 'locked_at' => now()]);
        $service = app(DescriptionSnippetUsageService::class);

        try {
            $service->applySnippet($admin, $grade, $snippet);
            $this->fail('Rapor terkunci seharusnya menolak insert snippet.');
        } catch (LogicException) {
            $this->assertSame(null, $grade->fresh()->description);
        }

        try {
            $service->updateFinalDescription($admin, $grade, 'Tidak boleh berubah.');
            $this->fail('Rapor terkunci seharusnya menolak perubahan deskripsi.');
        } catch (LogicException) {
            $this->assertSame(null, $grade->fresh()->description);
        }

        $this->expectException(LogicException::class);
        $service->saveAsSnippet($admin, $grade, 'Tidak boleh disimpan');
    }

    /** @return array{0: User, 1: User, 2: Subject, 3: Subject, 4: ReportGrade} */
    private function academicSetup(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Snippet Uji']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1]);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas 1 A', 'status' => 'ACTIVE']);
        $subject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK']);
        $otherSubject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'IPA', 'name' => 'IPA', 'short_name' => 'IPA']);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $guru->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
        $student = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-001', 'full_name' => 'Siswa Uji']);
        $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);
        $reportCard = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'status' => 'DRAFT']);
        $grade = ReportGrade::query()->create([
            'report_card_id' => $reportCard->id,
            'subject_assignment_id' => $assignment->id,
            'subject_name_snapshot' => $subject->name,
            'subject_short_name_snapshot' => $subject->short_name,
            'minimum_score_snapshot' => 70,
            'display_order_snapshot' => 1,
            'score' => 80,
            'predicate' => 'B',
            'status' => 'DRAFT',
        ]);

        return [$admin, $guru, $subject, $otherSubject, $grade];
    }

    private function snippet(User $owner, ?Subject $subject, array $attributes = []): DescriptionSnippet
    {
        return DescriptionSnippet::query()->create(array_merge([
            'institution_id' => $owner->institution_id,
            'owner_user_id' => $owner->id,
            'subject_id' => $subject?->id,
            'title' => 'Deskripsi umum',
            'content' => 'Menunjukkan perkembangan sesuai target.',
            'visibility' => DescriptionSnippetVisibility::Private->value,
            'active' => true,
        ], $attributes));
    }
}
