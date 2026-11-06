<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramLevelSeeder extends Seeder
{
    public function run(): void
    {
        Institution::query()->each(function (Institution $institution): void {
            $this->seedForInstitution($institution);
        });
    }

    public function seedForInstitution(Institution $institution): void
    {
        DB::transaction(function () use ($institution): void {
            foreach ($this->definitions() as $definition) {
                $program = Program::query()->firstOrCreate(
                    [
                        'institution_id' => $institution->getKey(),
                        'code' => $definition['code'],
                    ],
                    ['name' => $definition['name']],
                );

                foreach ($definition['levels'] as $number) {
                    $program->levels()->firstOrCreate(
                        ['number' => $number],
                        ['name' => 'Tingkat '.$number],
                    );
                }
            }
        });
    }

    /** @return array<int, array{code: string, name: string, levels: array<int, int>}> */
    private function definitions(): array
    {
        return [
            ['code' => 'PA', 'name' => 'Paket A', 'levels' => range(1, 6)],
            ['code' => 'PB', 'name' => 'Paket B', 'levels' => range(7, 9)],
            ['code' => 'PC', 'name' => 'Paket C', 'levels' => range(10, 12)],
        ];
    }
}
