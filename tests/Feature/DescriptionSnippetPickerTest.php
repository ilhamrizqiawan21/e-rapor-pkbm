<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Http\Controllers\Admin\DescriptionSnippetPickerController;
use App\Models\DescriptionSnippet;
use App\Models\Institution;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DescriptionSnippetPickerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::get('admin/description-snippets/picker', [DescriptionSnippetPickerController::class, 'index'])
                ->name('admin.description-snippets.picker');
        });
    }

    public function test_html_picker_filters_by_subject_and_returns_a_selection_target(): void
    {
        [$user, $institution] = $this->userWithInstitution(RoleCode::Guru);
        $subject = $this->subject($institution, 'BIND', 'Bahasa Indonesia');
        $otherSubject = $this->subject($institution, 'MAT', 'Matematika');
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);

        $shared = $this->snippet($institution, $user, $subject, [
            'title' => 'Membaca lancar',
            'content' => 'Peserta didik membaca teks dengan lancar.',
            'visibility' => 'SHARED',
        ]);
        $private = $this->snippet($institution, $user, $subject, [
            'title' => 'Catatan pribadi',
            'content' => 'Perlu penguatan pada intonasi.',
            'visibility' => 'PRIVATE',
        ]);
        $this->snippet($institution, User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey()]), $subject, [
            'title' => 'Milik guru lain',
            'content' => 'Tidak boleh muncul.',
            'visibility' => 'PRIVATE',
        ]);
        $this->snippet($institution, $user, $otherSubject, [
            'title' => 'Matematika',
            'content' => 'Tidak cocok dengan mapel.',
            'visibility' => 'SHARED',
        ]);
        $this->snippet($otherInstitution, $user, null, [
            'title' => 'Institusi lain',
            'content' => 'Tidak boleh muncul.',
            'visibility' => 'SHARED',
        ]);

        $response = $this->actingAs($user)->get('/admin/description-snippets/picker?'.http_build_query([
            'subject_id' => $subject->getKey(),
            'search' => 'lancar',
        ]));

        $response->assertOk()
            ->assertSee('Pilih deskripsi capaian')
            ->assertSee('Membaca lancar')
            ->assertSee('Peserta didik membaca teks dengan lancar.')
            ->assertSee('data-description-snippet-select', false)
            ->assertDontSee('Catatan pribadi')
            ->assertDontSee('Milik guru lain')
            ->assertDontSee('Institusi lain');

        $this->assertStringContainsString('&quot;id&quot;:'.$shared->getKey(), $response->getContent());
    }

    public function test_json_picker_returns_only_active_shared_or_owned_private_snippets(): void
    {
        [$user, $institution] = $this->userWithInstitution(RoleCode::Guru);
        $subject = $this->subject($institution, 'BIND', 'Bahasa Indonesia');
        $shared = $this->snippet($institution, $user, $subject, [
            'title' => 'Deskripsi bersama',
            'content' => 'Capaian bersama.',
            'visibility' => 'SHARED',
        ]);
        $private = $this->snippet($institution, $user, $subject, [
            'title' => 'Deskripsi pribadi',
            'content' => 'Capaian pribadi.',
            'visibility' => 'PRIVATE',
        ]);
        $inactive = $this->snippet($institution, $user, $subject, [
            'title' => 'Deskripsi nonaktif',
            'content' => 'Tidak boleh muncul.',
            'active' => false,
            'visibility' => 'SHARED',
        ]);

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->get('/admin/description-snippets/picker?subject_id='.$subject->getKey());

        $response->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('filters.subject_id', $subject->getKey())
            ->assertJsonPath('data.0.subject_name', 'Bahasa Indonesia');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($shared->getKey(), $ids);
        $this->assertContains($private->getKey(), $ids);
        $this->assertNotContains($inactive->getKey(), $ids);
        $this->assertSame('Capaian pribadi.', collect($response->json('data'))->firstWhere('id', $private->getKey())['content']);
    }

    public function test_picker_rejects_cross_institution_subject_and_inactive_user(): void
    {
        [$user, $institution] = $this->userWithInstitution(RoleCode::Guru);
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Lain']);
        $otherSubject = $this->subject($otherInstitution, 'BIND', 'Bahasa Lain');

        $this->actingAs($user)
            ->get('/admin/description-snippets/picker?subject_id='.$otherSubject->getKey())
            ->assertNotFound();

        $user->update(['status' => 'INACTIVE']);
        $this->actingAs($user)->get('/admin/description-snippets/picker')->assertForbidden();
    }

    /** @return array{0: User, 1: Institution} */
    private function userWithInstitution(RoleCode $role): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Uji']);
        $user = User::factory()->withRole($role)->create(['institution_id' => $institution->getKey()]);

        return [$user, $institution];
    }

    private function subject(Institution $institution, string $code, string $name): Subject
    {
        return Subject::query()->create([
            'institution_id' => $institution->getKey(),
            'code' => $code,
            'name' => $name,
            'short_name' => $code,
            'active' => true,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function snippet(Institution $institution, User $owner, ?Subject $subject, array $overrides = []): DescriptionSnippet
    {
        return DescriptionSnippet::query()->create(array_merge([
            'institution_id' => $institution->getKey(),
            'owner_user_id' => $owner->getKey(),
            'subject_id' => $subject?->getKey(),
            'title' => 'Deskripsi',
            'content' => 'Konten deskripsi.',
            'visibility' => 'PRIVATE',
            'active' => true,
        ], $overrides));
    }
}
