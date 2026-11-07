<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Enums\UserStatus;
use App\Models\Institution;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserRoleFoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_database_seeder_creates_active_demo_users_for_each_application_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $guru = User::query()->where('username', 'guru')->firstOrFail();
        $kepsek = User::query()->where('username', 'kepsek')->firstOrFail();

        $this->assertSame(3, User::query()->count());
        $this->assertSame(3, $admin->roles()->count() + $guru->roles()->count() + $kepsek->roles()->count());
        $this->assertTrue($admin->hasRole(RoleCode::Admin));
        $this->assertTrue($guru->hasRole(RoleCode::Guru));
        $this->assertTrue($kepsek->hasRole(RoleCode::Kepsek));
        $this->assertTrue($admin->isActive());
        $this->assertSame(UserStatus::Active, $guru->status);
        $this->assertSame('Kepala PKBM', $kepsek->staffProfile->title);
        $this->assertSame($admin->institution_id, $guru->institution_id);
    }

    public function test_user_factory_can_attach_an_institution_role_and_inactive_status(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Factory']);
        $user = User::factory()
            ->inactive()
            ->withRole(RoleCode::Guru)
            ->create(['institution_id' => $institution->getKey()]);

        $this->assertModelExists($user);
        $this->assertSame($institution->getKey(), $user->institution->getKey());
        $this->assertTrue($user->hasAnyRole([RoleCode::Guru, RoleCode::Kepsek]));
        $this->assertFalse($user->isActive());
        $this->assertSame(UserStatus::Inactive, $user->status);
    }

    public function test_database_seeder_can_skip_demo_academic_data_for_online_installation(): void
    {
        $this->app['config']->set('erapor.seed_demo_data', false);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::query()->count());
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('academic_years', 0);
        $this->assertDatabaseCount('subjects', 0);
    }
}
