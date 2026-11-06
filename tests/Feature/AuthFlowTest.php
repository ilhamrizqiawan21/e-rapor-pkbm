<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_login_with_username_and_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->withRole(RoleCode::Admin)->create([
            'username' => 'admin-login',
            'password' => 'OldPass1',
        ]);

        $response = $this->post(route('login.store'), [
            'login' => 'admin-login',
            'password' => 'OldPass1',
        ]);

        $response->assertRedirect(route('dashboard.admin'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_guru_can_login_with_email_and_is_redirected_to_guru_dashboard(): void
    {
        $guru = User::factory()->withRole(RoleCode::Guru)->create([
            'email' => 'guru-login@erapor.test',
            'password' => 'OldPass1',
        ]);

        $this->post(route('login.store'), [
            'login' => 'guru-login@erapor.test',
            'password' => 'OldPass1',
        ])->assertRedirect(route('dashboard.guru'));

        $this->assertAuthenticatedAs($guru);
    }

    public function test_invalid_credentials_return_a_validation_error(): void
    {
        $this->post(route('login.store'), [
            'login' => 'not-found',
            'password' => 'WrongPass1',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'username' => 'inactive-user',
            'password' => 'OldPass1',
        ]);

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'OldPass1',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        RateLimiter::clear('login:'.hash('sha256', 'rate-limited|127.0.0.1'));

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'login' => 'rate-limited',
                'password' => 'WrongPass1',
            ]);
        }

        $this->post(route('login.store'), [
            'login' => 'rate-limited',
            'password' => 'WrongPass1',
        ])->assertSessionHasErrors(['login' => 'Terlalu banyak percobaan masuk. Coba lagi dalam 60 detik.']);
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_authenticated_user_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => 'OldPass1']);

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'OldPass1',
                'password' => 'NewPass1',
                'password_confirmation' => 'NewPass1',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Password berhasil diperbarui.');

        $this->assertTrue(Hash::check('NewPass1', $user->refresh()->password));
    }

    public function test_admin_can_reset_a_user_password_within_their_institution(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Auth']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);
        $target = User::factory()->create(['institution_id' => $institution->getKey(), 'password' => 'OldPass1']);

        $this->actingAs($admin)
            ->put(route('admin.users.password.update', $target), [
                'password' => 'NewPass1',
                'password_confirmation' => 'NewPass1',
            ])
            ->assertSessionHas('status', 'Password pengguna berhasil direset oleh Admin.');

        $this->assertTrue(Hash::check('NewPass1', $target->refresh()->password));
    }

    public function test_non_admin_cannot_reset_a_user_password(): void
    {
        $guru = User::factory()->withRole(RoleCode::Guru)->create();
        $target = User::factory()->create(['password' => 'OldPass1']);

        $this->actingAs($guru)
            ->get(route('admin.users.password.edit', $target))
            ->assertForbidden();
    }

    public function test_user_with_an_email_can_request_a_password_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@erapor.test']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', 'Tautan reset password telah dikirim ke email terdaftar.');

        Notification::assertSentTo($user, ResetPassword::class);
    }
}
