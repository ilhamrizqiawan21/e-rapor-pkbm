<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2eSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $institution = Institution::query()->firstOrFail();

        User::query()->updateOrCreate(
            ['username' => 'inactive'],
            [
                'institution_id' => $institution->getKey(),
                'name' => 'Pengguna Nonaktif E2E',
                'email' => 'inactive@erapor.test',
                'password' => Hash::make('password'),
                'status' => UserStatus::Inactive,
            ],
        );
    }
}
