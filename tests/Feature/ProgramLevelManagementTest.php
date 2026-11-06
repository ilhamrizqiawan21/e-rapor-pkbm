<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\ProgramController;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramLevelSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProgramLevelManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::resource('admin/programs', ProgramController::class)->names('admin.programs');
            Route::resource('admin/programs.levels', LevelController::class)->names('admin.programs.levels');
        });
    }

    public function test_admin_can_manage_program_and_custom_level(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();

        $response = $this->actingAs($admin)->post(route('admin.programs.store'), [
            'code' => 'custom',
            'name' => 'Program Custom',
        ]);

        $program = Program::query()->where('institution_id', $institution->getKey())->firstOrFail();
        $response->assertRedirect(route('admin.programs.show', $program));
        $this->assertSame('CUSTOM', $program->code);

        $this->actingAs($admin)->post(route('admin.programs.levels.store', $program), [
            'number' => 4,
            'name' => 'Tahap Dasar Custom',
        ])->assertRedirect(route('admin.programs.show', $program));

        $this->assertDatabaseHas('levels', [
            'program_id' => $program->getKey(),
            'number' => 4,
            'name' => 'Tahap Dasar Custom',
        ]);

        $this->actingAs($admin)->put(route('admin.programs.update', $program), [
            'code' => 'CUSTOM-2',
            'name' => 'Program Custom Baru',
        ])->assertRedirect(route('admin.programs.show', $program));

        $this->assertDatabaseHas('programs', ['id' => $program->getKey(), 'code' => 'CUSTOM-2']);
    }

    public function test_program_level_seed_is_idempotent_and_preserves_custom_level_name(): void
    {
        [, $institution] = $this->adminWithInstitution();
        $seeder = new ProgramLevelSeeder;

        $seeder->seedForInstitution($institution);
        $level = Level::query()
            ->whereHas('program', fn ($query) => $query->where('institution_id', $institution->getKey())->where('code', 'PA'))
            ->where('number', 1)
            ->firstOrFail();
        $level->update(['name' => 'Kelas Awal Custom']);

        $seeder->seedForInstitution($institution);

        $this->assertSame(3, Program::query()->where('institution_id', $institution->getKey())->count());
        $this->assertSame(12, Level::query()->whereHas('program', fn ($query) => $query->where('institution_id', $institution->getKey()))->count());
        $this->assertSame('Kelas Awal Custom', $level->refresh()->name);
        $this->assertSame(6, Program::query()->where('institution_id', $institution->getKey())->where('code', 'PA')->firstOrFail()->levels()->count());
        $this->assertSame(3, Program::query()->where('institution_id', $institution->getKey())->where('code', 'PB')->firstOrFail()->levels()->count());
        $this->assertSame(3, Program::query()->where('institution_id', $institution->getKey())->where('code', 'PC')->firstOrFail()->levels()->count());
    }

    public function test_level_number_is_limited_to_one_through_twelve_and_unique_per_program(): void
    {
        [$admin] = $this->adminWithInstitution();
        $program = Program::query()->create([
            'institution_id' => $admin->institution_id,
            'code' => 'PA',
            'name' => 'Paket A',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.programs.levels.create', $program))
            ->post(route('admin.programs.levels.store', $program), ['number' => 13, 'name' => 'Invalid'])
            ->assertRedirect(route('admin.programs.levels.create', $program))
            ->assertSessionHasErrors('number');

        $program->levels()->create(['number' => 1, 'name' => 'Tingkat 1']);
        $this->actingAs($admin)
            ->from(route('admin.programs.levels.create', $program))
            ->post(route('admin.programs.levels.store', $program), ['number' => 1, 'name' => 'Duplikat'])
            ->assertRedirect(route('admin.programs.levels.create', $program))
            ->assertSessionHasErrors('number');
    }

    public function test_non_admin_cannot_create_or_update_program_and_cross_institution_is_forbidden(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();
        $program = Program::query()->create(['institution_id' => $institution->getKey(), 'code' => 'PA', 'name' => 'Paket A']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey()]);
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);
        $otherProgram = Program::query()->create(['institution_id' => $otherInstitution->getKey(), 'code' => 'PA', 'name' => 'Paket A']);

        $this->actingAs($guru)->post(route('admin.programs.store'), ['code' => 'PB', 'name' => 'Paket B'])->assertForbidden();
        $this->actingAs($guru)->put(route('admin.programs.update', $program), ['code' => 'PX', 'name' => 'Tidak boleh'])->assertForbidden();
        $this->actingAs($admin)->get(route('admin.programs.show', $otherProgram))->assertForbidden();
    }

    /** @return array{0: User, 1: Institution} */
    private function adminWithInstitution(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Uji']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);

        return [$admin, $institution];
    }
}
