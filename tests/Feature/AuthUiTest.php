<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AuthUiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_exposes_accessible_auth_controls(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Username atau email');
        $response->assertSee('autocomplete="username"', false);
        $response->assertSee('autocomplete="current-password"', false);
        $response->assertSee('Tampilkan');
        $response->assertSee('isSubmitting');
        $response->assertSee('Lupa password?');
    }

    public function test_login_validation_error_has_global_and_field_accessible_state(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['login' => 'Kredensial tidak valid.']));

        $this->view('auth.login', ['errors' => $errors])
            ->assertSee('Periksa kembali data yang diisi.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('role="alert"', false);
    }

    public function test_authenticated_profile_displays_account_and_role_information(): void
    {
        $user = User::factory()->withRole(RoleCode::Kepsek)->create([
            'name' => 'Kepala PKBM Uji',
            'username' => 'kepsek-uji',
            'email' => 'kepsek-uji@erapor.test',
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profil pengguna')
            ->assertSee('Kepala PKBM Uji')
            ->assertSee('kepsek-uji')
            ->assertSee('Kepala PKBM');
    }

    public function test_authenticated_header_exposes_profile_password_logout_and_active_role(): void
    {
        $user = User::factory()->withRole(RoleCode::Admin)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Profil saya')
            ->assertSee('Ubah password')
            ->assertSee('Keluar')
            ->assertSee('Role: Admin');
    }

    public function test_role_dashboard_routes_are_restricted_to_the_matching_role(): void
    {
        $guru = User::factory()->withRole(RoleCode::Guru)->create();

        $this->actingAs($guru)
            ->get(route('dashboard.admin'))
            ->assertForbidden();
    }
}
