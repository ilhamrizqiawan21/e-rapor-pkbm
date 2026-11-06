<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Http\Controllers\Admin\SubjectController;
use App\Models\Institution;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SubjectManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::resource('admin/subjects', SubjectController::class)->names('admin.subjects');
        });
    }

    public function test_admin_can_create_update_view_and_delete_subject_with_legger_preview(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();

        $response = $this->actingAs($admin)->post(route('admin.subjects.store'), [
            'code' => 'bind',
            'name' => ' Bahasa Indonesia ',
            'short_name' => ' b. indo ',
            'category' => 'Umum',
            'display_order' => 2,
            'active' => 1,
        ]);

        $subject = Subject::query()->where('institution_id', $institution->getKey())->firstOrFail();
        $response->assertRedirect(route('admin.subjects.show', $subject));
        $this->assertSame('BIND', $subject->code);
        $this->assertSame('B. INDO', $subject->short_name);

        $this->actingAs($admin)->get(route('admin.subjects.show', $subject))
            ->assertOk()
            ->assertSee('Header leger')
            ->assertSee('B. INDO')
            ->assertSee('Deskripsi capaian kompetensi');

        $this->actingAs($admin)->put(route('admin.subjects.update', $subject), [
            'code' => 'MAT',
            'name' => 'Matematika',
            'short_name' => 'MTK',
            'category' => 'Umum',
            'display_order' => 1,
            'active' => 0,
        ])->assertRedirect(route('admin.subjects.show', $subject));

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->getKey(),
            'code' => 'MAT',
            'display_order' => 1,
            'active' => 0,
        ]);

        $this->actingAs($admin)->delete(route('admin.subjects.destroy', $subject))
            ->assertRedirect(route('admin.subjects.index'));
        $this->assertDatabaseMissing('subjects', ['id' => $subject->getKey()]);
    }

    public function test_subject_code_is_unique_per_institution_and_short_name_is_required(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();
        Subject::query()->create([
            'institution_id' => $institution->getKey(),
            'code' => 'BIND',
            'name' => 'Bahasa Indonesia',
            'short_name' => 'B. INDO',
            'display_order' => 1,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.subjects.create'))
            ->post(route('admin.subjects.store'), [
                'code' => 'bind',
                'name' => 'Duplikat',
                'display_order' => 2,
                'active' => 1,
            ])
            ->assertRedirect(route('admin.subjects.create'))
            ->assertSessionHasErrors(['code', 'short_name']);
    }

    public function test_index_search_and_status_filter_are_scoped_to_institution(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();
        Subject::query()->create(['institution_id' => $institution->getKey(), 'code' => 'BIND', 'name' => 'Bahasa Indonesia', 'short_name' => 'B. INDO', 'display_order' => 1, 'active' => true]);
        Subject::query()->create(['institution_id' => $institution->getKey(), 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK', 'display_order' => 2, 'active' => false]);
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);
        Subject::query()->create(['institution_id' => $otherInstitution->getKey(), 'code' => 'BIND', 'name' => 'Bahasa Lain', 'short_name' => 'BL', 'display_order' => 1, 'active' => true]);

        $this->actingAs($admin)->get(route('admin.subjects.index', ['search' => 'Bahasa', 'active' => '1']))
            ->assertOk()
            ->assertSee('Bahasa Indonesia')
            ->assertDontSee('Matematika')
            ->assertDontSee('Bahasa Lain');
    }

    public function test_non_admin_can_view_subjects_but_cannot_mutate_them_or_cross_institution(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();
        $subject = Subject::query()->create(['institution_id' => $institution->getKey(), 'code' => 'BIND', 'name' => 'Bahasa Indonesia', 'short_name' => 'B. INDO', 'display_order' => 1, 'active' => true]);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey()]);
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);
        $otherSubject = Subject::query()->create(['institution_id' => $otherInstitution->getKey(), 'code' => 'BIND', 'name' => 'Bahasa Lain', 'short_name' => 'BL', 'display_order' => 1, 'active' => true]);

        $this->actingAs($guru)->get(route('admin.subjects.index'))->assertOk()->assertSee('Bahasa Indonesia');
        $this->actingAs($guru)->post(route('admin.subjects.store'), [])->assertForbidden();
        $this->actingAs($guru)->put(route('admin.subjects.update', $subject), [])->assertForbidden();
        $this->actingAs($admin)->get(route('admin.subjects.show', $otherSubject))->assertForbidden();
    }

    /** @return array{0: User, 1: Institution} */
    private function adminWithInstitution(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Uji']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);

        return [$admin, $institution];
    }
}
