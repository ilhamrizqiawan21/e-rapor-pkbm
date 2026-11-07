<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class UiComponentsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_page_loads_with_responsive_drawer_and_tokens(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('E-Rapor PKBM');
        $response->assertSee('mobileSidebarOpen', false);
        $response->assertSee('Buka menu navigasi');
    }

    public function test_components_showcase_page_loads_successfully(): void
    {
        $response = $this->actingAs(User::factory()->withRole(RoleCode::Admin)->create())
            ->get(route('components.showcase'));

        $response->assertStatus(200);
        $response->assertSee('Showcase Komponen UI');
        $response->assertSee('Komponen Tombol');
    }

    public function test_button_component_renders_as_button_and_anchor(): void
    {
        $button = $this->blade('<x-button variant="primary">Simpan Nilai</x-button>');
        $button->assertSee('Simpan Nilai');
        $button->assertSee('type="button"', false);
        $button->assertSee('bg-brand-900', false);

        $link = $this->blade('<x-button href="https://example.com" variant="secondary">Batal</x-button>');
        $link->assertSee('Batal');
        $link->assertSee('href="https://example.com"', false);
    }

    public function test_card_component_renders_a_consistent_surface(): void
    {
        $card = $this->blade('<x-card class="h-full" padding="lg">Ringkasan data</x-card>');

        $card->assertSee('Ringkasan data');
        $card->assertSee('rounded-2xl', false);
        $card->assertSee('shadow-xs', false);
        $card->assertSee('h-full', false);
        $card->assertSee('sm:p-8', false);
    }

    public function test_input_component_renders_label_hint_and_error(): void
    {
        $input = $this->blade('<x-input label="NISN Siswa" name="nisn" hint="10 digit nomor induk" />');
        $input->assertSee('NISN Siswa');
        $input->assertSee('name="nisn"', false);
        $input->assertSee('10 digit nomor induk');

        $inputWithError = $this->blade('<x-input name="email" error="Format email salah" />');
        $inputWithError->assertSee('Format email salah');
        $inputWithError->assertSee('border-rose-300', false);
    }

    public function test_select_component_renders_options(): void
    {
        $select = $this->blade('<x-select label="Kelas" name="class_id" :options="[\'1\' => \'Paket A\', \'2\' => \'Paket B\']" />');
        $select->assertSee('Kelas');
        $select->assertSee('value="1"', false);
        $select->assertSee('Paket A');
        $select->assertSee('appearance-none', false);
        $select->assertSee('focus:ring-2', false);
        $select->assertSee('pointer-events-none absolute inset-y-0 right-0', false);
    }

    public function test_select_component_supports_styled_multiple_controls(): void
    {
        $select = $this->blade('<x-select label="Mata pelajaran" name="subject_ids[]" multiple><option value="1" selected>Matematika</option></x-select>');

        $select->assertSee('multiple', false);
        $select->assertSee('min-h-40', false);
        $select->assertSee('Matematika');
        $select->assertDontSee('pointer-events-none absolute inset-y-0 right-0', false);
    }

    public function test_textarea_component_renders_rows_and_value(): void
    {
        $textarea = $this->blade('<x-textarea label="Deskripsi" rows="4">Catatan capaian belajar</x-textarea>');
        $textarea->assertSee('Deskripsi');
        $textarea->assertSee('rows="4"', false);
        $textarea->assertSee('Catatan capaian belajar');
    }

    public function test_table_component_renders_tabular_data(): void
    {
        $table = $this->blade('
            <x-table>
                <x-slot:head>
                    <tr><x-table.th>Nama</x-table.th></tr>
                </x-slot:head>
                <x-table.tr>
                    <x-table.td>Budi</x-table.td>
                </x-table.tr>
            </x-table>
        ');

        $table->assertSee('Nama');
        $table->assertSee('Budi');
    }

    public function test_badge_component_maps_statuses_and_renders_dots(): void
    {
        $approvedBadge = $this->blade('<x-badge variant="APPROVED" dot />');
        $approvedBadge->assertSee('Disetujui');
        $approvedBadge->assertSee('bg-emerald-50', false);

        $lockedBadge = $this->blade('<x-badge variant="LOCKED" />');
        $lockedBadge->assertSee('Terkunci');
        $lockedBadge->assertSee('bg-amber-50', false);
    }

    public function test_modal_component_renders_trigger_and_dialog(): void
    {
        $modal = $this->blade('<x-modal name="test-modal" title="Judul Modal">Isi Modal Dialog</x-modal>');
        $modal->assertSee('Judul Modal');
        $modal->assertSee('Isi Modal Dialog');
        $modal->assertSee('test-modal');
    }

    public function test_toast_component_renders_container_and_alpine_listener(): void
    {
        $toast = $this->blade('<x-toast />');
        $toast->assertSee('@toast.window', false);
    }

    public function test_pagination_component_renders_with_paginator(): void
    {
        $paginator = new LengthAwarePaginator(
            items: range(1, 10),
            total: 30,
            perPage: 10,
            currentPage: 1
        );

        $pagination = $this->blade('<x-pagination :paginator="$paginator" />', ['paginator' => $paginator]);
        $pagination->assertSee('Menampilkan');
        $pagination->assertSee('dari');
        $pagination->assertSee('30');
    }

    public function test_empty_state_component_renders(): void
    {
        $empty = $this->blade('<x-empty-state title="Belum Ada Siswa" description="Tambah siswa pertama" actionText="Tambah" />');
        $empty->assertSee('Belum Ada Siswa');
        $empty->assertSee('Tambah siswa pertama');
        $empty->assertSee('Tambah');
    }

    public function test_loading_component_renders(): void
    {
        $loading = $this->blade('<x-loading text="Memuat raport..." />');
        $loading->assertSee('Memuat raport...');
        $loading->assertSee('animate-spin', false);
    }

    public function test_error_state_component_renders(): void
    {
        $error = $this->blade('<x-error-state title="Gagal Memuat" message="Terjadi galat pada server" retryText="Ulangi" />');
        $error->assertSee('Gagal Memuat');
        $error->assertSee('Terjadi galat pada server');
        $error->assertSee('Ulangi');
    }
}
