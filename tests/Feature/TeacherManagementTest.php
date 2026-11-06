<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Enums\UserStatus;
use App\Http\Controllers\Admin\TeacherController;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TeacherManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
            Route::resource('teachers', TeacherController::class)->parameters(['teachers' => 'user']);
        });
    }

    public function test_admin_can_create_a_teacher_account_and_staff_profile(): void
    {
        [$institution, $admin] = $this->adminSetup();

        $this->actingAs($admin)->post(route('admin.teachers.store'), [
            'name' => '  Guru Baru  ',
            'username' => '  guru.baru  ',
            'email' => 'guru.baru@example.test',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
            'employee_number' => 'NIY-001',
            'title' => 'Tutor Matematika',
            'phone' => '08123456789',
            'status' => 'ACTIVE',
        ])->assertRedirect();

        $teacher = User::query()->where('username', 'guru.baru')->firstOrFail();
        $this->assertSame($institution->id, $teacher->institution_id);
        $this->assertTrue($teacher->hasRole(RoleCode::Guru));
        $this->assertTrue(Hash::check('SecurePass1', $teacher->password));
        $this->assertSame('NIY-001', $teacher->staffProfile->employee_number);
        $this->assertSame('Tutor Matematika', $teacher->staffProfile->title);
    }

    public function test_username_and_email_must_be_unique(): void
    {
        [$institution, $admin] = $this->adminSetup();
        User::factory()->withRole(RoleCode::Guru)->create([
            'institution_id' => $institution->id,
            'username' => 'guru-lama',
            'email' => 'guru-lama@example.test',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.teachers.create'))
            ->post(route('admin.teachers.store'), [
                'name' => 'Guru Duplikat',
                'username' => 'guru-lama',
                'email' => 'guru-lama@example.test',
                'password' => 'SecurePass1',
                'password_confirmation' => 'SecurePass1',
                'status' => 'ACTIVE',
            ])
            ->assertRedirect(route('admin.teachers.create'))
            ->assertSessionHasErrors(['username', 'email']);
    }

    public function test_admin_can_filter_and_search_teachers_by_status_and_employee_number(): void
    {
        [$institution, $admin] = $this->adminSetup();
        $active = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id, 'name' => 'Guru Aktif', 'username' => 'guru-aktif']);
        $inactive = User::factory()->withRole(RoleCode::Guru)->inactive()->create(['institution_id' => $institution->id, 'name' => 'Guru Nonaktif', 'username' => 'guru-nonaktif']);
        StaffProfile::query()->create(['user_id' => $active->id, 'employee_number' => 'NIY-AKTIF']);
        StaffProfile::query()->create(['user_id' => $inactive->id, 'employee_number' => 'NIY-NONAKTIF']);

        $this->actingAs($admin)
            ->get(route('admin.teachers.index', ['status' => 'ACTIVE', 'search' => 'NIY-AKTIF']))
            ->assertOk()
            ->assertSee('Guru Aktif')
            ->assertDontSee('Guru Nonaktif');
    }

    public function test_admin_can_update_teacher_and_change_status(): void
    {
        [$institution, $admin] = $this->adminSetup();
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);

        $this->actingAs($admin)->put(route('admin.teachers.update', $teacher), [
            'name' => 'Guru Diperbarui',
            'username' => 'guru-diperbarui',
            'email' => 'guru-diperbarui@example.test',
            'employee_number' => 'NIY-UPDATED',
            'title' => 'Tutor Senior',
            'phone' => '0800000000',
            'status' => 'INACTIVE',
        ])->assertRedirect(route('admin.teachers.show', $teacher));

        $this->assertSame('Guru Diperbarui', $teacher->refresh()->name);
        $this->assertSame(UserStatus::Inactive, $teacher->status);
        $this->assertSame('NIY-UPDATED', $teacher->staffProfile->employee_number);
    }

    public function test_non_admin_roles_cannot_manage_teachers(): void
    {
        [$institution, $admin] = $this->adminSetup();
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $kepsek = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $institution->id]);

        foreach ([$teacher, $kepsek] as $user) {
            $this->actingAs($user)->get(route('admin.teachers.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.teachers.create'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.teachers.edit', $teacher))->assertForbidden();
        }

        $this->assertTrue($admin->hasRole(RoleCode::Admin));
    }

    public function test_teacher_from_another_institution_cannot_be_managed(): void
    {
        [, $admin] = $this->adminSetup();
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $otherInstitution->id]);

        $this->actingAs($admin)->get(route('admin.teachers.show', $teacher))->assertForbidden();
    }

    public function test_teacher_with_assignment_history_is_deactivated_instead_of_deleted(): void
    {
        [$institution, $admin] = $this->adminSetup();
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'PA', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        AcademicClass::query()->create([
            'academic_year_id' => $year->id,
            'program_id' => $program->id,
            'level_id' => $level->id,
            'homeroom_teacher_id' => $teacher->id,
            'code' => 'PA-1A',
            'name' => 'Kelas 1A',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($admin)->delete(route('admin.teachers.destroy', $teacher))->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'status' => UserStatus::Inactive->value]);
    }

    public function test_teacher_without_assignment_history_can_be_deleted(): void
    {
        [$institution, $admin] = $this->adminSetup();
        $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);

        $this->actingAs($admin)->delete(route('admin.teachers.destroy', $teacher))->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
    }

    /**
     * @return array{0: Institution, 1: User}
     */
    private function adminSetup(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Guru Uji']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);

        return [$institution, $admin];
    }
}
