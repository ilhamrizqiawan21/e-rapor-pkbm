<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\Institution;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $institution = Institution::query()->firstOrCreate(
            ['name' => 'PKBM Contoh'],
            ['address' => 'Lengkapi alamat PKBM'],
        );

        $roles = collect(RoleCode::cases())->mapWithKeys(function (RoleCode $role): array {
            $model = Role::query()->updateOrCreate(
                ['code' => $role->value],
                ['name' => $role->label()],
            );

            return [$role->value => $model];
        });

        $demoUsers = [
            [
                'role' => RoleCode::Admin,
                'name' => 'Admin Demo',
                'username' => 'admin',
                'email' => 'admin@erapor.test',
                'employee_number' => 'ADM-001',
                'title' => 'Administrator',
            ],
            [
                'role' => RoleCode::Guru,
                'name' => 'Guru Demo',
                'username' => 'guru',
                'email' => 'guru@erapor.test',
                'employee_number' => 'GRU-001',
                'title' => 'Guru',
            ],
            [
                'role' => RoleCode::Kepsek,
                'name' => 'Kepala PKBM Demo',
                'username' => 'kepsek',
                'email' => 'kepsek@erapor.test',
                'employee_number' => 'KPK-001',
                'title' => 'Kepala PKBM',
            ],
        ];

        foreach ($demoUsers as $demoUser) {
            $user = User::query()->firstOrCreate(
                ['username' => $demoUser['username']],
                [
                    'institution_id' => $institution->id,
                    'name' => $demoUser['name'],
                    'email' => $demoUser['email'],
                    'password' => 'password',
                ],
            );

            $user->forceFill([
                'institution_id' => $institution->id,
                'name' => $demoUser['name'],
                'email' => $demoUser['email'],
                'status' => 'ACTIVE',
            ])->save();

            $user->roles()->syncWithoutDetaching([$roles[$demoUser['role']->value]->getKey()]);

            StaffProfile::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'employee_number' => $demoUser['employee_number'],
                    'title' => $demoUser['title'],
                ],
            );
        }
    }
}
