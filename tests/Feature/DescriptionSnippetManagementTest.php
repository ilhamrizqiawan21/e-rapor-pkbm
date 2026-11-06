<?php

namespace Tests\Feature;

use App\Enums\DescriptionSnippetVisibility;
use App\Enums\RoleCode;
use App\Http\Controllers\Admin\DescriptionSnippetController;
use App\Models\DescriptionSnippet;
use App\Models\Institution;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DescriptionSnippetManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::resource('admin/description-snippets', DescriptionSnippetController::class)
                ->names('admin.description-snippets');
        });
    }

    public function test_admin_can_create_update_view_and_delete_shared_snippet(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey(), 'name' => 'Guru Pemilik']);
        $subject = $this->subject($institution, 'MAT', 'Matematika');

        $response = $this->actingAs($admin)->post(route('admin.description-snippets.store'), [
            'title' => '  Konsep bilangan  ',
            'content' => "  Mampu memahami konsep bilangan.\nTerus berlatih secara mandiri.  ",
            'subject_id' => $subject->getKey(),
            'owner_user_id' => $guru->getKey(),
            'visibility' => 'SHARED',
            'active' => 1,
        ]);

        $snippet = DescriptionSnippet::query()->firstOrFail();
        $response->assertRedirect(route('admin.description-snippets.show', $snippet));
        $this->assertSame('Konsep bilangan', $snippet->title);
        $this->assertSame(DescriptionSnippetVisibility::Shared, $snippet->visibility);
        $this->assertSame($guru->getKey(), $snippet->owner_user_id);

        $this->actingAs($admin)->get(route('admin.description-snippets.show', $snippet))
            ->assertOk()
            ->assertSee('Mampu memahami konsep bilangan.')
            ->assertSee('Guru Pemilik');

        $this->actingAs($admin)->put(route('admin.description-snippets.update', $snippet), [
            'title' => 'Konsep bilangan diperbarui',
            'content' => 'Mampu memahami konsep bilangan dengan baik.',
            'subject_id' => $subject->getKey(),
            'owner_user_id' => $guru->getKey(),
            'visibility' => 'SHARED',
            'active' => 0,
        ])->assertRedirect(route('admin.description-snippets.show', $snippet));

        $this->assertDatabaseHas('description_snippets', [
            'id' => $snippet->getKey(),
            'title' => 'Konsep bilangan diperbarui',
            'active' => 0,
        ]);

        $this->actingAs($admin)->delete(route('admin.description-snippets.destroy', $snippet))
            ->assertRedirect(route('admin.description-snippets.index'));
        $this->assertDatabaseMissing('description_snippets', ['id' => $snippet->getKey()]);
    }

    public function test_guru_can_create_and_manage_only_own_private_snippets(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey(), 'name' => 'Guru Satu']);
        $otherGuru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey(), 'name' => 'Guru Dua']);

        $response = $this->actingAs($guru)->post(route('admin.description-snippets.store'), [
            'title' => 'Capaian pribadi',
            'content' => 'Menunjukkan perkembangan yang baik.',
            'visibility' => 'PRIVATE',
            'active' => 1,
        ]);

        $private = DescriptionSnippet::query()->where('title', 'Capaian pribadi')->firstOrFail();
        $response->assertRedirect(route('admin.description-snippets.show', $private));
        $this->assertSame($guru->getKey(), $private->owner_user_id);
        $this->assertSame(DescriptionSnippetVisibility::Private, $private->visibility);

        $shared = DescriptionSnippet::query()->create([
            'institution_id' => $institution->getKey(),
            'owner_user_id' => $otherGuru->getKey(),
            'title' => 'Capaian bersama',
            'content' => 'Dapat digunakan bersama.',
            'visibility' => DescriptionSnippetVisibility::Shared,
            'active' => true,
        ]);

        $this->assertTrue($guru->can('use', $shared));

        $this->actingAs($guru)->get(route('admin.description-snippets.index'))
            ->assertOk()
            ->assertSee('Capaian pribadi')
            ->assertSee('Capaian bersama');

        $this->actingAs($guru)->put(route('admin.description-snippets.update', $private), [
            'title' => 'Capaian pribadi diperbarui',
            'content' => 'Deskripsi pribadi diperbarui.',
            'visibility' => 'PRIVATE',
            'active' => 1,
        ])->assertRedirect(route('admin.description-snippets.show', $private));

        $this->actingAs($guru)->put(route('admin.description-snippets.update', $shared), [
            'title' => 'Tidak boleh',
            'content' => 'Tidak boleh',
            'visibility' => 'PRIVATE',
            'active' => 1,
        ])->assertForbidden();

        $otherPrivate = DescriptionSnippet::query()->create([
            'institution_id' => $institution->getKey(),
            'owner_user_id' => $otherGuru->getKey(),
            'title' => 'Pribadi guru dua',
            'content' => 'Hanya untuk guru dua.',
            'visibility' => DescriptionSnippetVisibility::Private,
            'active' => true,
        ]);

        $this->actingAs($guru)->get(route('admin.description-snippets.show', $otherPrivate))->assertForbidden();
        $this->actingAs($guru)->delete(route('admin.description-snippets.destroy', $shared))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.description-snippets.show', $private))->assertForbidden();
    }

    public function test_admin_cannot_create_private_and_owner_must_be_guru_in_same_institution(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();

        $this->actingAs($admin)
            ->from(route('admin.description-snippets.create'))
            ->post(route('admin.description-snippets.store'), [
                'title' => 'Tidak valid',
                'content' => 'Isi',
                'visibility' => 'PRIVATE',
                'owner_user_id' => $admin->getKey(),
                'active' => 1,
            ])
            ->assertRedirect(route('admin.description-snippets.create'))
            ->assertSessionHasErrors(['visibility', 'owner_user_id']);

        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);
        $otherGuru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $otherInstitution->getKey()]);

        $this->actingAs($admin)
            ->post(route('admin.description-snippets.store'), [
                'title' => 'Pemilik beda institusi',
                'content' => 'Isi',
                'visibility' => 'SHARED',
                'owner_user_id' => $otherGuru->getKey(),
                'active' => 1,
            ])
            ->assertSessionHasErrors('owner_user_id');

        $this->assertDatabaseMissing('description_snippets', ['title' => 'Pemilik beda institusi']);
    }

    public function test_index_search_subject_and_ownership_filters_are_scoped_to_visible_snippets(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey()]);
        $subject = $this->subject($institution, 'BIND', 'Bahasa Indonesia');
        $otherSubject = $this->subject($institution, 'MAT', 'Matematika');

        DescriptionSnippet::query()->create([
            'institution_id' => $institution->getKey(),
            'owner_user_id' => $guru->getKey(),
            'subject_id' => $subject->getKey(),
            'title' => 'Milik guru bahasa',
            'content' => 'Membaca dengan lancar.',
            'visibility' => DescriptionSnippetVisibility::Private,
            'active' => true,
        ]);
        DescriptionSnippet::query()->create([
            'institution_id' => $institution->getKey(),
            'subject_id' => $otherSubject->getKey(),
            'title' => 'Shared matematika',
            'content' => 'Memecahkan masalah.',
            'visibility' => DescriptionSnippetVisibility::Shared,
            'active' => true,
        ]);
        DescriptionSnippet::query()->create([
            'institution_id' => $institution->getKey(),
            'subject_id' => $subject->getKey(),
            'title' => 'Shared bahasa',
            'content' => 'Membaca bersama.',
            'visibility' => DescriptionSnippetVisibility::Shared,
            'active' => true,
        ]);

        $this->actingAs($guru)->get(route('admin.description-snippets.index', ['ownership' => 'mine']))
            ->assertOk()
            ->assertSee('Milik guru bahasa')
            ->assertDontSee('Shared matematika');

        $this->actingAs($admin)->get(route('admin.description-snippets.index', ['subject_id' => $subject->getKey(), 'search' => 'bahasa']))
            ->assertOk()
            ->assertSee('Shared bahasa')
            ->assertDontSee('Milik guru bahasa')
            ->assertDontSee('Shared matematika');
    }

    public function test_shared_snippets_are_not_visible_across_institutions(): void
    {
        [$admin, $institution] = $this->adminWithInstitution();
        $snippet = DescriptionSnippet::query()->create([
            'institution_id' => $institution->getKey(),
            'title' => 'Snippet institusi pertama',
            'content' => 'Konten institusi pertama.',
            'visibility' => DescriptionSnippetVisibility::Shared,
            'active' => true,
        ]);
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);
        $otherAdmin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $otherInstitution->getKey()]);

        $this->actingAs($otherAdmin)->get(route('admin.description-snippets.index'))
            ->assertOk()
            ->assertDontSee('Snippet institusi pertama');
        $this->actingAs($otherAdmin)->get(route('admin.description-snippets.show', $snippet))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.description-snippets.show', $snippet))->assertOk();
    }

    /** @return array{0: User, 1: Institution} */
    private function adminWithInstitution(): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Uji']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);

        return [$admin, $institution];
    }

    private function subject(Institution $institution, string $code, string $name): Subject
    {
        return Subject::query()->create([
            'institution_id' => $institution->getKey(),
            'code' => $code,
            'name' => $name,
            'short_name' => $code,
            'display_order' => 1,
            'active' => true,
        ]);
    }
}
