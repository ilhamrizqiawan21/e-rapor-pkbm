@extends('layouts.app')

@section('content')
    @php($hasFilters = request()->filled('search') || request()->filled('subject_id') || request()->filled('ownership') || request()->filled('active'))
    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Penilaian / reusable content" title="Bank deskripsi" description="Simpan kalimat capaian yang sering dipakai agar Guru dapat memilihnya lalu menyesuaikannya untuk setiap peserta didik.">
            @can('create', App\Models\DescriptionSnippet::class)<x-button href="{{ route('admin.description-snippets.create') }}">Tambah snippet</x-button>@endcan
        </x-page-header>

        @if (session('status'))<div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status"><svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" /></svg><span>{{ session('status') }}</span></div>@endif

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="snippet-directory-heading">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6"><div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div><h2 id="snippet-directory-heading" class="text-lg font-semibold text-slate-950">Direktori bank deskripsi</h2><p class="mt-1 text-sm text-slate-500">Gunakan filter untuk memisahkan snippet milik sendiri, dibagikan, dan status aktif.</p></div><span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $snippets->total() }} snippet</span></div></div>
            <form method="GET" action="{{ route('admin.description-snippets.index') }}" class="grid gap-4 border-b border-slate-100 bg-slate-50/70 px-5 py-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_14rem_12rem_10rem_auto_auto] xl:items-end sm:px-6">
                <x-input name="search" label="Cari snippet" :value="request('search')" placeholder="Judul atau isi deskripsi" autocomplete="off" />
                <x-select name="subject_id" label="Mata pelajaran" :selected="request('subject_id')" :options="$subjects->pluck('name', 'id')->toArray()" placeholder="Semua mapel" />
                <x-select name="ownership" label="Kepemilikan" :selected="request('ownership')" :options="['mine' => 'Milik saya', 'shared' => 'Dibagikan']" placeholder="Semua" />
                <x-select name="active" label="Status" :selected="request('active')" :options="['1' => 'Aktif', '0' => 'Nonaktif']" placeholder="Semua" />
                <x-button type="submit" variant="secondary">Terapkan filter</x-button>
                @if ($hasFilters)<x-button href="{{ route('admin.description-snippets.index') }}" variant="ghost">Reset</x-button>@else<span class="hidden xl:block"></span>@endif
            </form>
            <div class="px-5 py-3 text-xs text-slate-500 sm:px-6">Snippet dibagikan akan tersedia untuk Guru sesuai hak aksesnya.</div>

            <div class="hidden overflow-x-auto md:block"><table class="min-w-full divide-y divide-slate-200 text-sm"><caption class="sr-only">Daftar bank deskripsi</caption><thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><x-table.th>Snippet</x-table.th><x-table.th>Mapel</x-table.th><x-table.th>Kepemilikan</x-table.th><x-table.th>Status</x-table.th><x-table.th align="right">Aksi</x-table.th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($snippets as $snippet)
                    <tr class="align-top transition-colors hover:bg-slate-50"><x-table.td><a href="{{ route('admin.description-snippets.show', $snippet) }}" class="font-semibold text-brand-900 hover:text-brand-700 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">{{ $snippet->title }}</a><p class="mt-1 max-w-xl whitespace-pre-line text-xs leading-5 text-slate-500">{{ str($snippet->content)->limit(180) }}</p></x-table.td><x-table.td>@if ($snippet->subject)<span class="font-medium text-slate-800">{{ $snippet->subject->name }}</span><p class="mt-1 text-xs text-slate-500">{{ $snippet->subject->short_name }}</p>@else<span class="text-slate-500">Semua mapel</span>@endif</x-table.td><x-table.td><x-badge :variant="$snippet->isShared() ? 'info' : 'brand'">{{ $snippet->isShared() ? 'Dibagikan' : 'Milik saya' }}</x-badge>@if ($snippet->owner)<p class="mt-1 text-xs text-slate-500">{{ $snippet->owner->name }}</p>@endif</x-table.td><x-table.td><x-badge :variant="$snippet->active ? 'ACTIVE' : 'INACTIVE'" dot>{{ $snippet->active ? 'Aktif' : 'Nonaktif' }}</x-badge></x-table.td><x-table.td align="right"><div class="flex flex-wrap justify-end gap-2"><x-button href="{{ route('admin.description-snippets.show', $snippet) }}" variant="secondary" size="sm">Lihat detail</x-button>@can('update', $snippet)<x-button href="{{ route('admin.description-snippets.edit', $snippet) }}" variant="ghost" size="sm">Edit</x-button>@endcan</div></x-table.td></tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12">
                        @if ($hasFilters)
                            <x-empty-state title="Tidak ada snippet" description="Belum ada snippet yang cocok dengan filter saat ini.">
                                <x-slot:actions><x-button href="{{ route('admin.description-snippets.index') }}" variant="secondary" size="sm">Reset filter</x-button></x-slot:actions>
                            </x-empty-state>
                        @else
                            @can('create', App\Models\DescriptionSnippet::class)
                                <x-empty-state title="Belum ada snippet" description="Tambahkan snippet pertama agar deskripsi capaian dapat digunakan ulang.">
                                    <x-slot:actions><x-button href="{{ route('admin.description-snippets.create') }}" size="sm">Tambah snippet</x-button></x-slot:actions>
                                </x-empty-state>
                            @else
                                <x-empty-state title="Belum ada snippet" description="Belum ada bank deskripsi pada institusi ini." />
                            @endcan
                        @endif
                    </td></tr>
                @endforelse
            </tbody></table></div>
            <div class="space-y-3 p-4 md:hidden">@forelse ($snippets as $snippet)<article class="rounded-2xl border border-slate-200 p-4" data-responsive-card><div class="flex items-start justify-between gap-3"><div><a href="{{ route('admin.description-snippets.show', $snippet) }}" class="font-semibold text-brand-900">{{ $snippet->title }}</a><p class="mt-1 text-xs text-slate-500">{{ $snippet->subject?->name ?? 'Semua mapel' }}</p></div><x-badge :variant="$snippet->active ? 'ACTIVE' : 'INACTIVE'" dot>{{ $snippet->active ? 'Aktif' : 'Nonaktif' }}</x-badge></div><p class="mt-3 text-sm leading-5 text-slate-600">{{ str($snippet->content)->limit(180) }}</p><div class="mt-3 flex flex-wrap items-center gap-2"><x-badge :variant="$snippet->isShared() ? 'info' : 'brand'">{{ $snippet->isShared() ? 'Dibagikan' : 'Milik saya' }}</x-badge>@if ($snippet->owner)<span class="text-xs text-slate-500">{{ $snippet->owner->name }}</span>@endif</div><div class="mt-4 flex flex-wrap gap-2"><x-button href="{{ route('admin.description-snippets.show', $snippet) }}" variant="secondary" size="sm">Lihat detail</x-button>@can('update', $snippet)<x-button href="{{ route('admin.description-snippets.edit', $snippet) }}" variant="ghost" size="sm">Edit</x-button>@endcan</div></article>@empty<div class="px-2 py-10 text-center"><p class="font-semibold text-slate-900">Tidak ada snippet</p><p class="mt-2 text-sm text-slate-500">Belum ada snippet yang cocok dengan filter saat ini.</p></div>@endforelse</div>
            <div class="border-t border-slate-100 px-5 sm:px-6"><x-pagination :paginator="$snippets" /></div>
        </section>
    </div>
@endsection
