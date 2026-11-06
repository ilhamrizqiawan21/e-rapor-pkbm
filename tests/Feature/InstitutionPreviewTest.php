<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstitutionPreviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_open_preview_with_identity_format_and_signatory_data(): void
    {
        $institution = Institution::query()->create([
            'name' => 'PKBM Preview',
            'address' => 'Jl. Preview No. 1',
            'npsn' => '12345678',
        ]);
        $admin = User::factory()->withRole(RoleCode::Admin)->create([
            'institution_id' => $institution->getKey(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.institutions.preview', $institution))
            ->assertOk()
            ->assertSee('Preview identitas rapor')
            ->assertSee('PKBM Preview')
            ->assertSee('Jl. Preview No. 1')
            ->assertSee('12345678')
            ->assertSee('Area isi nilai, KKM, predikat, deskripsi, sikap, dan absensi');
    }

    public function test_institution_update_creates_audit_log(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Lama']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create([
            'institution_id' => $institution->getKey(),
        ]);

        $this->actingAs($admin)->put(route('admin.institutions.update', $institution), [
            'name' => 'PKBM Baru',
            'address' => 'Alamat Baru',
            'email' => 'baru@pkbm.test',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'institution_id' => $institution->getKey(),
            'user_id' => $admin->getKey(),
            'action' => 'institution.updated',
            'entity_type' => Institution::class,
            'entity_id' => $institution->getKey(),
        ]);
    }

    public function test_preview_renders_a_public_disk_logo_when_enabled(): void
    {
        Storage::fake('public');
        $institution = Institution::query()->create([
            'name' => 'PKBM Logo Preview',
            'logo_path' => 'institutions/1/logo.png',
        ]);
        $admin = User::factory()->withRole(RoleCode::Admin)->create([
            'institution_id' => $institution->getKey(),
        ]);
        Storage::disk('public')->put($institution->logo_path, 'logo');

        $this->actingAs($admin)
            ->get(route('admin.institutions.preview', $institution))
            ->assertOk()
            ->assertSee('Logo PKBM Logo Preview', false);
    }
}
