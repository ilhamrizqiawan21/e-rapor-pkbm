<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Role;
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
        $institution = Institution::query()->create([
            'name' => 'PKBM Contoh',
            'address' => 'Lengkapi alamat PKBM',
        ]);

        $roles = collect([
            ['code' => 'ADMIN', 'name' => 'Admin'],
            ['code' => 'GURU', 'name' => 'Guru'],
            ['code' => 'KEPSEK', 'name' => 'Kepsek'],
        ])->mapWithKeys(function (array $role): array {
            $model = Role::query()->create($role);

            return [$model->code => $model];
        });

        $admin = User::factory()->create([
            'institution_id' => $institution->id,
            'name' => 'Admin Demo',
            'email' => 'admin@erapor.test',
        ]);

        $admin->roles()->attach($roles['ADMIN']);
    }
}
