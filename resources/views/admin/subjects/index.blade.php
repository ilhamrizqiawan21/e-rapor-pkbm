@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master data</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Mata pelajaran</h2>
                <p class="mt-2 text-sm text-slate-600">Kelola mapel per institusi, termasuk singkatan yang tampil pada leger.</p>
            </div>
            @can('create', App\Models\Subject::class)
                <x-button href="{{ route('admin.subjects.create') }}">Tambah mata pelajaran</x-button>
            @endcan
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <form method="GET" action="{{ route('admin.subjects.index') }}" class="grid gap-4 md:grid-cols-[1fr_12rem_auto_auto] md:items-end">
                <x-input name="search" label="Cari mata pelajaran" :value="request('search')" placeholder="Kode, nama, singkatan, atau kategori" />
                <x-select name="active" label="Status" :selected="request('active')" :options="['1' => 'Aktif', '0' => 'Nonaktif']" placeholder="Semua status" />
                <x-button type="submit" variant="secondary">Terapkan</x-button>
                <x-button href="{{ route('admin.subjects.index') }}" variant="ghost">Reset</x-button>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr><x-table.th>Urutan</x-table.th><x-table.th>Kode</x-table.th><x-table.th>Mata pelajaran</x-table.th><x-table.th>Kategori</x-table.th><x-table.th>Status</x-table.th><x-table.th align="right">Aksi</x-table.th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($subjects as $subject)
                            <tr class="align-top hover:bg-slate-50">
                                <x-table.td>{{ $subject->display_order }}</x-table.td>
                                <x-table.td><span class="font-semibold text-slate-900">{{ $subject->code }}</span><p class="mt-1 text-xs text-slate-500">Leger: {{ $subject->short_name }}</p></x-table.td>
                                <x-table.td><a href="{{ route('admin.subjects.show', $subject) }}" class="font-semibold text-brand-900 hover:text-brand-700">{{ $subject->name }}</a></x-table.td>
                                <x-table.td>{{ $subject->category ?: '—' }}</x-table.td>
                                <x-table.td><x-badge :variant="$subject->active ? 'ACTIVE' : 'INACTIVE'" dot>{{ $subject->active ? 'Aktif' : 'Nonaktif' }}</x-badge></x-table.td>
                                <x-table.td align="right"><div class="flex flex-wrap justify-end gap-2"><x-button href="{{ route('admin.subjects.show', $subject) }}" variant="secondary" size="sm">Detail</x-button>@can('update', $subject)<x-button href="{{ route('admin.subjects.edit', $subject) }}" variant="ghost" size="sm">Edit</x-button>@endcan</div></x-table.td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada mata pelajaran yang sesuai filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6"><x-pagination :paginator="$subjects" /></div>
        </section>
    </div>
@endsection
