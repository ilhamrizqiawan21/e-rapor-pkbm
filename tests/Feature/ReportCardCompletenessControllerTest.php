<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Http\Controllers\Admin\ReportCardCompletenessController;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\HomeroomNote;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\ReportCard;
use App\Models\ReportExtracurricular;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportCardCompletenessControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::get('admin/report-cards/{report_card}/completeness', [ReportCardCompletenessController::class, 'edit'])
                ->name('admin.report-cards.completeness.edit');
            Route::put('admin/report-cards/{report_card}/extracurriculars', [ReportCardCompletenessController::class, 'updateExtracurriculars'])
                ->name('admin.report-cards.completeness.extracurriculars.update');
            Route::put('admin/report-cards/{report_card}/homeroom-note', [ReportCardCompletenessController::class, 'updateHomeroomNote'])
                ->name('admin.report-cards.completeness.homeroom-note.update');
            Route::get('admin/description-snippets/picker', fn () => response()->json([]))
                ->name('admin.description-snippets.picker');
        });
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_homeroom_teacher_can_open_empty_completeness_form(): void
    {
        [$guru, $reportCard] = $this->reportCardSetup();

        $this->actingAs($guru)
            ->get(route('admin.report-cards.completeness.edit', $reportCard))
            ->assertOk()
            ->assertSee('Ekstrakurikuler')
            ->assertSee('Belum ada kegiatan ekstrakurikuler.')
            ->assertSee('Catatan wali kelas')
            ->assertSee('/500 karakter')
            ->assertSee('Gunakan bank deskripsi');
    }

    public function test_homeroom_teacher_can_replace_and_reorder_activities(): void
    {
        [$guru, $reportCard] = $this->reportCardSetup();

        $this->actingAs($guru)
            ->put(route('admin.report-cards.completeness.extracurriculars.update', $reportCard), [
                'rows' => [
                    ['activity_name' => '  Pramuka ', 'description' => 'Aktif dan disiplin.'],
                    ['activity_name' => ' ', 'description' => ' '],
                    ['activity_name' => 'PMR', 'description' => 'Membantu kegiatan sosial.'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseCount('report_extracurriculars', 2);
        $this->assertDatabaseHas('report_extracurriculars', [
            'report_card_id' => $reportCard->id,
            'activity_name' => 'Pramuka',
            'display_order' => 1,
        ]);
        $this->assertDatabaseHas('report_extracurriculars', [
            'report_card_id' => $reportCard->id,
            'activity_name' => 'PMR',
            'display_order' => 2,
        ]);

        $this->actingAs($guru)
            ->put(route('admin.report-cards.completeness.extracurriculars.update', $reportCard), [
                'rows' => [
                    ['activity_name' => 'PMR', 'description' => 'Diperbarui.'],
                    ['activity_name' => 'Pramuka', 'description' => 'Tetap aktif.'],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(
            ['PMR', 'Pramuka'],
            ReportExtracurricular::query()
                ->where('report_card_id', $reportCard->id)
                ->orderBy('display_order')
                ->pluck('activity_name')
                ->all(),
        );
    }

    public function test_homeroom_teacher_can_save_and_clear_note(): void
    {
        [$guru, $reportCard] = $this->reportCardSetup();

        $this->actingAs($guru)
            ->put(route('admin.report-cards.completeness.homeroom-note.update', $reportCard), [
                'note' => 'Pertahankan semangat belajar dan tanggung jawab.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('homeroom_notes', [
            'report_card_id' => $reportCard->id,
            'note' => 'Pertahankan semangat belajar dan tanggung jawab.',
        ]);

        $this->actingAs($guru)
            ->put(route('admin.report-cards.completeness.homeroom-note.update', $reportCard), ['note' => '   '])
            ->assertRedirect();

        $this->assertDatabaseMissing('homeroom_notes', ['report_card_id' => $reportCard->id]);
    }

    public function test_non_homeroom_guru_cannot_edit_completeness(): void
    {
        [$homeroom, $reportCard] = $this->reportCardSetup();
        $otherGuru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $homeroom->institution_id]);

        $this->actingAs($otherGuru)
            ->get(route('admin.report-cards.completeness.edit', $reportCard))
            ->assertForbidden();
    }

    public function test_locked_report_card_rejects_completeness_updates(): void
    {
        [$guru, $reportCard] = $this->reportCardSetup(['status' => 'LOCKED']);
        ReportExtracurricular::query()->create([
            'report_card_id' => $reportCard->id,
            'activity_name' => 'Pramuka',
            'description' => 'Lama',
            'display_order' => 1,
        ]);
        HomeroomNote::query()->create(['report_card_id' => $reportCard->id, 'note' => 'Catatan lama']);

        $this->actingAs($guru)
            ->put(route('admin.report-cards.completeness.extracurriculars.update', $reportCard), [
                'rows' => [['activity_name' => 'Tidak boleh', 'description' => '']],
            ])
            ->assertForbidden();
        $this->actingAs($guru)
            ->put(route('admin.report-cards.completeness.homeroom-note.update', $reportCard), ['note' => 'Tidak boleh'])
            ->assertForbidden();

        $this->assertDatabaseHas('report_extracurriculars', ['report_card_id' => $reportCard->id, 'activity_name' => 'Pramuka']);
        $this->assertDatabaseHas('homeroom_notes', ['report_card_id' => $reportCard->id, 'note' => 'Catatan lama']);
    }

    public function test_note_limit_is_validated(): void
    {
        [$guru, $reportCard] = $this->reportCardSetup();

        $this->actingAs($guru)
            ->from(route('admin.report-cards.completeness.edit', $reportCard))
            ->put(route('admin.report-cards.completeness.homeroom-note.update', $reportCard), [
                'note' => str_repeat('x', 501),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('note');

        $this->assertDatabaseMissing('homeroom_notes', ['report_card_id' => $reportCard->id]);
    }

    /** @param array<string, mixed> $reportCardAttributes */
    private function reportCardSetup(array $reportCardAttributes = []): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Kelengkapan Rapor']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create([
            'institution_id' => $institution->id,
            'name' => '2026/2027',
            'status' => 'ACTIVE',
        ]);
        $semester = Semester::query()->create([
            'academic_year_id' => $year->id,
            'code' => 'GANJIL',
            'name' => 'Ganjil',
            'sequence' => 1,
            'status' => 'ACTIVE',
        ]);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create([
            'academic_year_id' => $year->id,
            'program_id' => $program->id,
            'level_id' => $level->id,
            'homeroom_teacher_id' => $guru->id,
            'code' => 'A-1',
            'name' => 'Kelas 1 A',
            'status' => 'ACTIVE',
        ]);
        $student = Student::query()->create([
            'institution_id' => $institution->id,
            'student_number' => 'PD-001',
            'full_name' => 'Siswa Wali Kelas',
            'status' => 'ACTIVE',
        ]);
        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
            'start_date' => '2026-07-01',
            'enrollment_status' => 'ACTIVE',
        ]);
        $reportCard = ReportCard::query()->create([
            'enrollment_id' => $enrollment->id,
            'semester_id' => $semester->id,
            'status' => 'DRAFT',
            ...$reportCardAttributes,
        ]);

        return [$guru, $reportCard->refresh()];
    }
}
