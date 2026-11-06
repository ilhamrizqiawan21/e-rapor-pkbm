<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Http\Controllers\Admin\InstitutionController;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstitutionManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::resource('admin/institutions', InstitutionController::class)
                ->names('admin.institutions');
            Route::delete('admin/institutions/{institution}/logo', [InstitutionController::class, 'destroyLogo'])
                ->name('admin.institutions.logo.destroy');
        });
    }

    public function test_guest_is_redirected_from_institution_management(): void
    {
        $this->get(route('admin.institutions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_view_their_institution_identity_form(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();

        $this->actingAs($admin)
            ->get(route('admin.institutions.edit', $institution))
            ->assertOk()
            ->assertSee('Edit identitas PKBM')
            ->assertSee('Nama PKBM')
            ->assertSee('NPSN')
            ->assertSee('Nomor izin operasional')
            ->assertSee('Kode pos')
            ->assertSee('Logo PKBM')
            ->assertSee('Hapus / bersihkan logo');
    }

    public function test_admin_can_update_identity_and_replace_logo(): void
    {
        Storage::fake('public');
        [$admin, $institution] = $this->adminWithInstitution();
        $oldLogo = 'institutions/'.$institution->getKey().'/old.png';
        Storage::disk('public')->put($oldLogo, 'old logo');
        $institution->update(['logo_path' => $oldLogo]);

        $newLogo = UploadedFile::fake()->image('pkbm-baru.png', 400, 200);

        $response = $this->actingAs($admin)->put(route('admin.institutions.update', $institution), [
            'name' => '  PKBM Harapan Bangsa  ',
            'address' => 'Jl. Pendidikan No. 1',
            'postal_code' => '40123',
            'phone' => '+62 812-3456-7890',
            'email' => 'ADMIN@PKBM.TEST',
            'npsn' => '12345678',
            'permit_number' => 'IZIN-2026-001',
            'logo' => $newLogo,
        ]);

        $response->assertRedirect(route('admin.institutions.edit', $institution));
        $this->assertDatabaseHas('institutions', [
            'id' => $institution->getKey(),
            'name' => 'PKBM Harapan Bangsa',
            'postal_code' => '40123',
            'email' => 'admin@pkbm.test',
            'npsn' => '12345678',
            'permit_number' => 'IZIN-2026-001',
        ]);

        $institution->refresh();
        $this->assertNotSame($oldLogo, $institution->logo_path);
        Storage::disk('public')->assertMissing($oldLogo);
        Storage::disk('public')->assertExists($institution->logo_path);
    }

    public function test_admin_can_remove_existing_logo(): void
    {
        Storage::fake('public');
        [$admin, $institution] = $this->adminWithInstitution();
        $logoPath = 'institutions/'.$institution->getKey().'/logo.png';
        Storage::disk('public')->put($logoPath, 'logo');
        $institution->update(['logo_path' => $logoPath]);

        $this->actingAs($admin)
            ->delete('/admin/institutions/'.$institution->getKey().'/logo')
            ->assertRedirect(route('admin.institutions.edit', $institution));

        $this->assertNull($institution->refresh()->logo_path);
        Storage::disk('public')->assertMissing($logoPath);
    }

    public function test_identity_validation_rejects_invalid_fields_and_logo(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();

        $this->actingAs($admin)
            ->from(route('admin.institutions.edit', $institution))
            ->put(route('admin.institutions.update', $institution), [
                'name' => '',
                'postal_code' => 'abc',
                'phone' => 'not a phone',
                'email' => 'not-an-email',
                'logo' => UploadedFile::fake()->create('logo.pdf', 3000, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.institutions.edit', $institution))
            ->assertSessionHasErrors(['name', 'postal_code', 'phone', 'email', 'logo']);
    }

    public function test_admin_cannot_manage_another_institution(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);

        $this->actingAs($admin)
            ->get(route('admin.institutions.edit', $otherInstitution))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_update_institution_identity(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Guru']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create([
            'institution_id' => $institution->getKey(),
        ]);

        $this->actingAs($guru)
            ->get(route('admin.institutions.edit', $institution))
            ->assertForbidden();

        $this->actingAs($guru)
            ->put(route('admin.institutions.update', $institution), ['name' => 'Tidak boleh'])
            ->assertForbidden();
    }

    public function test_institution_deletion_remains_blocked_by_policy(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();

        $this->actingAs($admin)
            ->delete(route('admin.institutions.destroy', $institution))
            ->assertForbidden();

        $this->assertDatabaseHas('institutions', ['id' => $institution->getKey()]);
    }

    /**
     * @return array{0: User, 1: Institution}
     */
    private function adminWithInstitution(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Uji']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create([
            'institution_id' => $institution->getKey(),
        ]);

        return [$admin, $institution];
    }
}
