@extends('layouts.app')

@section('content')
    @php($hasFilters = request()->filled('search') || request()->filled('active'))
    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Data inti / konfigurasi akademik" title="Mata pelajaran" description="Kelola mapel per institusi, termasuk singkatan yang tampil pada leger.">
            @can('create', App\Models\Subject::class)<x-button href="#" icon="add" @click.prevent="$dispatch('open-modal', 'subject-create')">Tambah mata pelajaran</x-button>@endcan
        </x-page-header>

        @if (session('status'))<div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status"><svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" /></svg><span>{{ session('status') }}</span></div>@endif

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="subject-directory-heading">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6"><div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div><h2 id="subject-directory-heading" class="text-lg font-semibold text-slate-950">Direktori mata pelajaran</h2><p class="mt-1 text-sm text-slate-500">Kode, singkatan, kategori, dan status digunakan oleh input nilai serta leger.</p></div><span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $subjects->total() }} mapel</span></div></div>
            <form method="GET" action="{{ route('admin.subjects.index') }}" class="grid gap-4 border-b border-slate-100 bg-slate-50/70 px-5 py-4 md:grid-cols-[minmax(0,1fr)_12rem_auto_auto] md:items-end sm:px-6">
                <x-input name="search" label="Cari mata pelajaran" :value="request('search')" placeholder="Kode, nama, singkatan, atau kategori" autocomplete="off" />
                <x-select name="active" label="Status" :selected="request('active')" :options="['1' => 'Aktif', '0' => 'Nonaktif']" placeholder="Semua status" />
                <x-button type="submit" variant="secondary" icon="filter">Terapkan filter</x-button>
                @if ($hasFilters)<x-button href="{{ route('admin.subjects.index') }}" variant="ghost" icon="x">Reset</x-button>@else<span class="hidden md:block"></span>@endif
            </form>
            <div class="px-5 py-3 text-xs text-slate-500 sm:px-6">{{ $hasFilters ? 'Filter sedang aktif.' : 'Menampilkan seluruh mata pelajaran dalam institusi.' }}</div>

            <div class="hidden overflow-x-auto md:block"><table class="min-w-full divide-y divide-slate-200 text-sm"><caption class="sr-only">Daftar mata pelajaran</caption><thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><x-table.th>Urutan</x-table.th><x-table.th>Kode</x-table.th><x-table.th>Mata pelajaran</x-table.th><x-table.th>Kategori</x-table.th><x-table.th>Status</x-table.th><x-table.th align="right">Aksi</x-table.th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($subjects as $subject)
                    <tr class="align-top transition-colors hover:bg-slate-50"><x-table.td>{{ $subject->display_order }}</x-table.td><x-table.td><span class="font-semibold text-slate-900">{{ $subject->code }}</span><p class="mt-1 text-xs text-slate-500">Leger: {{ $subject->short_name }}</p></x-table.td><x-table.td><a href="{{ route('admin.subjects.show', $subject) }}" class="font-semibold text-brand-900 hover:text-brand-700 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">{{ $subject->name }}</a></x-table.td><x-table.td>{{ $subject->category ?: '—' }}</x-table.td><x-table.td><x-badge :variant="$subject->active ? 'ACTIVE' : 'INACTIVE'" dot>{{ $subject->active ? 'Aktif' : 'Nonaktif' }}</x-badge></x-table.td><x-table.td align="right"><div class="flex flex-wrap justify-end gap-2"><x-button href="{{ route('admin.subjects.show', $subject) }}" variant="detail" size="sm" icon="eye">Lihat detail</x-button>@can('update', $subject)<x-button href="#" @click.prevent="$dispatch('open-modal', 'subject-edit-{{ $subject->id }}')" variant="edit" size="sm" icon="edit">Edit</x-button>@endcan</div></x-table.td></tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-12">
                        @if ($hasFilters)
                            <x-empty-state title="Tidak ada mata pelajaran" description="Belum ada data yang cocok dengan filter saat ini.">
                                <x-slot:actions><x-button href="{{ route('admin.subjects.index') }}" variant="secondary" size="sm">Reset filter</x-button></x-slot:actions>
                            </x-empty-state>
                        @else
                            @can('create', App\Models\Subject::class)
                                <x-empty-state title="Belum ada mata pelajaran" description="Tambahkan mata pelajaran pertama untuk mulai mengatur input nilai dan leger.">
                                    <x-slot:actions><x-button href="#" @click.prevent="$dispatch('open-modal', 'subject-create')" size="sm" icon="add">Tambah mata pelajaran</x-button></x-slot:actions>
                                </x-empty-state>
                            @else
                                <x-empty-state title="Belum ada mata pelajaran" description="Belum ada data mata pelajaran pada institusi ini." />
                            @endcan
                        @endif
                    </td></tr>
                @endforelse
            </tbody></table></div>
            <div class="space-y-3 p-4 md:hidden">@forelse ($subjects as $subject)<article class="rounded-2xl border border-slate-200 p-4" data-responsive-card><div class="flex items-start justify-between gap-3"><div><a href="{{ route('admin.subjects.show', $subject) }}" class="font-semibold text-brand-900">{{ $subject->name }}</a><p class="mt-1 text-xs text-slate-500">{{ $subject->code }} · Leger: {{ $subject->short_name }}</p></div><x-badge :variant="$subject->active ? 'ACTIVE' : 'INACTIVE'" dot>{{ $subject->active ? 'Aktif' : 'Nonaktif' }}</x-badge></div><dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-xs text-slate-500">Urutan</dt><dd class="mt-1 font-medium text-slate-800">{{ $subject->display_order }}</dd></div><div><dt class="text-xs text-slate-500">Kategori</dt><dd class="mt-1 font-medium text-slate-800">{{ $subject->category ?: '—' }}</dd></div></dl><div class="mt-4 flex flex-wrap gap-2"><x-button href="{{ route('admin.subjects.show', $subject) }}" variant="detail" size="sm" icon="eye">Lihat detail</x-button>@can('update', $subject)<x-button href="#" @click.prevent="$dispatch('open-modal', 'subject-edit-{{ $subject->id }}')" variant="edit" size="sm" icon="edit">Edit</x-button>@endcan</div></article>@empty<div class="px-2 py-10 text-center"><p class="font-semibold text-slate-900">Tidak ada mata pelajaran</p><p class="mt-2 text-sm text-slate-500">Belum ada data yang cocok dengan filter saat ini.</p></div>@endforelse</div>
            <div class="border-t border-slate-100 px-5 sm:px-6"><x-pagination :paginator="$subjects" /></div>
        </section>

        @can('create', App\Models\Subject::class)
            <x-modal name="subject-create" title="Tambah mata pelajaran" max-width="3xl">
                <form method="POST" action="{{ route('admin.subjects.store') }}" class="space-y-5">
                    @csrf
                    @include('admin.subjects._form', ['subject' => $modalSubject])
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <x-button type="button" variant="cancel" icon="x" @click="$dispatch('close-modal', 'subject-create')">Batal</x-button>
                        <x-button type="submit" icon="save">Simpan mata pelajaran</x-button>
                    </div>
                </form>
            </x-modal>
        @endcan

        @foreach ($subjects as $subject)
            @can('update', $subject)
                <x-modal name="subject-edit-{{ $subject->id }}" title="Edit mata pelajaran" max-width="3xl">
                    <form method="POST" action="{{ route('admin.subjects.update', $subject) }}" class="space-y-5">
                        @csrf @method('PUT')
                        @include('admin.subjects._form', ['subject' => $subject])
                        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                            <x-button type="button" variant="cancel" icon="x" @click="$dispatch('close-modal', 'subject-edit-{{ $subject->id }}')">Batal</x-button>
                            <x-button type="submit" icon="save">Simpan perubahan</x-button>
                        </div>
                    </form>
                </x-modal>
            @endcan
        @endforeach
    </div>
@endsection
