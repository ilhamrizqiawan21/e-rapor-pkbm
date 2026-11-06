<?php

namespace Tests\Feature;

use App\Enums\AcademicYearStatus;
use App\Enums\RoleCode;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\SemesterController;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AcademicPeriodManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
            Route::resource('academic-years', AcademicYearController::class);
            Route::resource('academic-years.semesters', SemesterController::class)->except(['show']);
        });
    }

    public function test_admin_can_create_period_and_semester(): void
    {
        [$admin, $institution] = $this->adminContext();

        $response = $this->actingAs($admin)->post(route('admin.academic-years.store'), [
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'status' => 'ACTIVE',
        ]);

        $year = AcademicYear::query()->where('institution_id', $institution->getKey())->firstOrFail();
        $response->assertRedirect(route('admin.academic-years.show', $year));

        $this->actingAs($admin)->post(route('admin.academic-years.semesters.store', $year), [
            'code' => 'GANJIL',
            'name' => 'Semester Ganjil',
            'sequence' => 1,
            'status' => 'ACTIVE',
        ])->assertRedirect(route('admin.academic-years.show', $year));

        $this->assertDatabaseHas('semesters', [
            'academic_year_id' => $year->getKey(),
            'code' => 'GANJIL',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_period_name_is_unique_per_institution_and_dates_are_ordered(): void
    {
        [$admin, $institution] = $this->adminContext();
        AcademicYear::query()->create(['institution_id' => $institution->getKey(), 'name' => '2025/2026']);

        $this->actingAs($admin)
            ->from(route('admin.academic-years.create'))
            ->post(route('admin.academic-years.store'), [
                'name' => '2025/2026',
                'start_date' => '2026-07-01',
                'end_date' => '2026-06-30',
                'status' => 'DRAFT',
            ])
            ->assertRedirect(route('admin.academic-years.create'))
            ->assertSessionHasErrors(['name', 'end_date']);
    }

    public function test_only_one_active_period_and_semester_is_allowed(): void
    {
        [$admin, $institution] = $this->adminContext();
        $first = AcademicYear::query()->create(['institution_id' => $institution->getKey(), 'name' => '2025/2026', 'status' => AcademicYearStatus::Active]);
        $second = AcademicYear::query()->create(['institution_id' => $institution->getKey(), 'name' => '2026/2027']);

        $this->actingAs($admin)->put(route('admin.academic-years.update', $second), [
            'name' => $second->name,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'ACTIVE',
        ])->assertSessionHasErrors('status');

        $semester = Semester::query()->create(['academic_year_id' => $first->getKey(), 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1]);
        $this->actingAs($admin)->put(route('admin.academic-years.semesters.update', [$first, $semester]), [
            'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1, 'status' => 'ACTIVE',
        ])->assertRedirect(route('admin.academic-years.show', $first));

        $secondSemester = Semester::query()->create(['academic_year_id' => $first->getKey(), 'code' => 'GENAP', 'name' => 'Genap', 'sequence' => 2]);
        $this->actingAs($admin)->put(route('admin.academic-years.semesters.update', [$first, $secondSemester]), [
            'code' => 'GENAP', 'name' => 'Genap', 'sequence' => 2, 'status' => 'ACTIVE',
        ])->assertSessionHasErrors('status');
    }

    public function test_semester_active_requires_active_period_and_closed_changes_need_confirmation(): void
    {
        [$admin, $institution] = $this->adminContext();
        $year = AcademicYear::query()->create(['institution_id' => $institution->getKey(), 'name' => '2025/2026']);

        $this->actingAs($admin)->post(route('admin.academic-years.semesters.store', $year), [
            'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1, 'status' => 'ACTIVE',
        ])->assertSessionHasErrors('status');

        $semester = Semester::query()->create(['academic_year_id' => $year->getKey(), 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1]);
        $this->actingAs($admin)->put(route('admin.academic-years.update', $year), [
            'name' => $year->name, 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'status' => 'CLOSED',
        ])->assertSessionHasErrors('confirm_closure');

        $this->actingAs($admin)->put(route('admin.academic-years.update', $year), [
            'name' => $year->name, 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'status' => 'CLOSED', 'confirm_closure' => '1',
        ])->assertRedirect(route('admin.academic-years.show', $year));

        $this->actingAs($admin)->get(route('admin.academic-years.semesters.edit', [$year, $semester]))->assertForbidden();
    }

    public function test_period_with_semester_cannot_be_deleted(): void
    {
        [$admin, $institution] = $this->adminContext();
        $year = AcademicYear::query()->create(['institution_id' => $institution->getKey(), 'name' => '2025/2026']);
        Semester::query()->create(['academic_year_id' => $year->getKey(), 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1]);

        $this->actingAs($admin)->delete(route('admin.academic-years.destroy', $year))
            ->assertSessionHasErrors('academic_year');

        $this->assertDatabaseHas('academic_years', ['id' => $year->getKey()]);
    }

    public function test_guru_and_kepsek_can_view_their_period_but_cannot_manage_it(): void
    {
        [$admin, $institution] = $this->adminContext();
        $year = AcademicYear::query()->create(['institution_id' => $institution->getKey(), 'name' => '2025/2026']);
        $guru = $this->userWithRole($institution, RoleCode::Guru);
        $kepsek = $this->userWithRole($institution, RoleCode::Kepsek);
        $program = Program::query()->create([
            'institution_id' => $institution->getKey(),
            'code' => 'PA',
            'name' => 'Paket A',
        ]);
        $level = Level::query()->create([
            'program_id' => $program->getKey(),
            'number' => 1,
            'name' => 'Tingkat 1',
        ]);
        AcademicClass::query()->create([
            'academic_year_id' => $year->getKey(),
            'program_id' => $program->getKey(),
            'level_id' => $level->getKey(),
            'homeroom_teacher_id' => $guru->getKey(),
            'code' => 'PA-1A',
            'name' => 'Paket A Tingkat 1A',
        ]);

        foreach ([$guru, $kepsek] as $viewer) {
            $this->actingAs($viewer)->get(route('admin.academic-years.index'))->assertOk()->assertSee('2025/2026');
            $this->actingAs($viewer)->get(route('admin.academic-years.show', $year))->assertOk();
            $this->actingAs($viewer)->get(route('admin.academic-years.create'))->assertForbidden();
            $this->actingAs($viewer)->get(route('admin.academic-years.edit', $year))->assertForbidden();
        }

        $this->assertTrue($admin->institution_id === $institution->getKey());
    }

    /** @return array{0: User, 1: Institution} */
    private function adminContext(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Uji']);
        $admin = $this->userWithRole($institution, RoleCode::Admin);

        return [$admin, $institution];
    }

    private function userWithRole(Institution $institution, RoleCode $role): User
    {
        return User::factory()->withRole($role)->create(['institution_id' => $institution->getKey()]);
    }
}
