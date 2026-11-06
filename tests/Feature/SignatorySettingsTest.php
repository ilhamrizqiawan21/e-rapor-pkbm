<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Enums\SignatorySource;
use App\Http\Controllers\Admin\SignatorySettingsController;
use App\Models\Institution;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SignatorySettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::get('/__test/signatory-settings', [SignatorySettingsController::class, 'edit']);
            Route::put('/__test/signatory-settings', [SignatorySettingsController::class, 'update']);
        });
    }

    public function test_admin_can_render_signatory_settings_with_kepsek_accounts(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Test']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);
        $kepsek = User::factory()->withRole(RoleCode::Kepsek)->create([
            'institution_id' => $institution->getKey(),
            'name' => 'Kepala PKBM Test',
        ]);
        StaffProfile::query()->create([
            'user_id' => $kepsek->getKey(),
            'employee_number' => 'NIY-001',
            'title' => 'Kepala PKBM',
        ]);

        $response = $this->actingAs($admin)->get('/__test/signatory-settings');

        $response->assertOk()
            ->assertSee('Penandatangan rapor')
            ->assertSee('Kepala PKBM Test')
            ->assertSee('NIY-001')
            ->assertSee('Nama Kepala PKBM');
    }

    public function test_admin_can_save_account_based_signatory_source(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Account']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);
        $kepsek = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $institution->getKey()]);

        $response = $this->actingAs($admin)->put('/__test/signatory-settings', [
            'headmaster_source' => SignatorySource::Account->value,
            'headmaster_user_id' => $kepsek->getKey(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('institution_signatory_settings', [
            'institution_id' => $institution->getKey(),
            'headmaster_source' => SignatorySource::Account->value,
            'headmaster_user_id' => $kepsek->getKey(),
            'headmaster_name' => null,
        ]);
    }

    public function test_admin_can_save_manual_signatory_source(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Manual']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);

        $response = $this->actingAs($admin)->put('/__test/signatory-settings', [
            'headmaster_source' => SignatorySource::Manual->value,
            'headmaster_name' => 'Siti Rahma, S.Pd.',
            'headmaster_title' => 'Kepala PKBM',
            'headmaster_employee_number' => 'NIP-9988',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('institution_signatory_settings', [
            'institution_id' => $institution->getKey(),
            'headmaster_source' => SignatorySource::Manual->value,
            'headmaster_name' => 'Siti Rahma, S.Pd.',
            'headmaster_title' => 'Kepala PKBM',
            'headmaster_employee_number' => 'NIP-9988',
            'headmaster_user_id' => null,
        ]);
    }

    public function test_account_source_cannot_select_kepsek_from_another_institution(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Own']);
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Other']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);
        $otherKepsek = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $otherInstitution->getKey()]);

        $response = $this->actingAs($admin)->from('/__test/signatory-settings')->put('/__test/signatory-settings', [
            'headmaster_source' => SignatorySource::Account->value,
            'headmaster_user_id' => $otherKepsek->getKey(),
        ]);

        $response->assertRedirect('/__test/signatory-settings')->assertSessionHasErrors('headmaster_user_id');
        $this->assertDatabaseMissing('institution_signatory_settings', ['institution_id' => $institution->getKey()]);
    }

    public function test_non_admin_cannot_update_signatory_settings(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Guru']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey()]);

        $this->actingAs($guru)->put('/__test/signatory-settings', [
            'headmaster_source' => SignatorySource::Manual->value,
            'headmaster_name' => 'Tidak boleh',
            'headmaster_title' => 'Kepala PKBM',
        ])->assertForbidden();
    }
}
