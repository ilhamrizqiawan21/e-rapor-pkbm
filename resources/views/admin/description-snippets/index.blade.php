@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Penilaian</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Bank deskripsi</h2>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">Simpan kalimat capaian yang sering dipakai agar guru dapat memasukkannya ke deskripsi rapor lalu menyesuaikannya untuk setiap peserta didik.</p>
            </div>
            @can('create', App\Models\DescriptionSnippet::class)
                <x-button href="{{ route('admin.description-snippets.create') }}">Tambah snippet</x-button>
            @endcan
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <form method="GET" action="{{ route('admin.description-snippets.index') }}" class="grid gap-4 md:grid-cols-[1fr_14rem_12rem_10rem_auto_auto] md:items-end">
                <x-input name="search" label="Cari snippet" :value="request('search')" placeholder="Judul atau isi deskripsi" />
                <x-select name="subject_id" label="Mata pelajaran" :selected="request('subject_id')" :options="$subjects->pluck('name', 'id')->toArray()" placeholder="Semua mapel" />
                <x-select name="ownership" label="Kepemilikan" :selected="request('ownership')" :options="['mine' => 'Milik saya', 'shared' => 'Dibagikan']" placeholder="Semua" />
                <x-select name="active" label="Status" :selected="request('active')" :options="['1' => 'Aktif', '0' => 'Nonaktif']" placeholder="Semua" />
                <x-button type="submit" variant="secondary">Terapkan</x-button>
                <x-button href="{{ route('admin.description-snippets.index') }}" variant="ghost">Reset</x-button>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <x-table.th>Snippet</x-table.th>
                            <x-table.th>Mapel</x-table.th>
                            <x-table.th>Kepemilikan</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Aksi</x-table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($snippets as $snippet)
                            <tr class="align-top hover:bg-slate-50">
                                <x-table.td>
                                    <a href="{{ route('admin.description-snippets.show', $snippet) }}" class="font-semibold text-brand-900 hover:text-brand-700">{{ $snippet->title }}</a>
                                    <p class="mt-1 max-w-xl whitespace-pre-line text-xs leading-5 text-slate-500">{{ str($snippet->content)->limit(180) }}</p>
                                </x-table.td>
                                <x-table.td>
                                    @if ($snippet->subject)
                                        <span class="font-medium text-slate-800">{{ $snippet->subject->name }}</span>
                                        <p class="mt-1 text-xs text-slate-500">{{ $snippet->subject->short_name }}</p>
                                    @else
                                        <span class="text-slate-500">Semua mapel</span>
                                    @endif
                                </x-table.td>
                                <x-table.td>
                                    <x-badge :variant="$snippet->isShared() ? 'info' : 'brand'">{{ $snippet->isShared() ? 'Dibagikan' : 'Milik saya' }}</x-badge>
                                    @if ($snippet->owner)
                                        <p class="mt-1 text-xs text-slate-500">{{ $snippet->owner->name }}</p>
                                    @endif
                                </x-table.td>
                                <x-table.td><x-badge :variant="$snippet->active ? 'ACTIVE' : 'INACTIVE'" dot>{{ $snippet->active ? 'Aktif' : 'Nonaktif' }}</x-badge></x-table.td>
                                <x-table.td align="right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <x-button href="{{ route('admin.description-snippets.show', $snippet) }}" variant="secondary" size="sm">Lihat</x-button>
                                        @can('update', $snippet)
                                            <x-button href="{{ route('admin.description-snippets.edit', $snippet) }}" variant="ghost" size="sm">Edit</x-button>
                                        @endcan
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">Belum ada snippet yang sesuai filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6"><x-pagination :paginator="$snippets" /></div>
        </section>
    </div>
@endsection
