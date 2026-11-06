@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <x-page-header eyebrow="Mata pelajaran {{ $subject->code }}" title="{{ $subject->name }}" description="Singkatan leger: {{ $subject->short_name }}" back-href="{{ route('admin.subjects.index') }}" back-text="Kembali ke mata pelajaran">
                @can('update', $subject)<x-button href="{{ route('admin.subjects.edit', $subject) }}" variant="edit" icon="edit">Edit</x-button>@endcan
            </x-page-header>
        </div>

        @if (session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>@endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Detail master</h3>
                <dl class="mt-5 divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Kode</dt><dd class="font-semibold text-slate-900">{{ $subject->code }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Kategori</dt><dd class="text-right text-slate-900">{{ $subject->category ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Urutan</dt><dd class="text-slate-900">{{ $subject->display_order }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Status</dt><dd><x-badge :variant="$subject->active ? 'ACTIVE' : 'INACTIVE'" dot>{{ $subject->active ? 'Aktif' : 'Nonaktif' }}</x-badge></dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Penugasan kelas</dt><dd class="text-slate-900">{{ $subject->assignments_count }}</dd></div>
                </dl>
                @can('delete', $subject)
                    <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}" class="mt-5 border-t border-slate-100 pt-5">
                        @csrf @method('DELETE')
                        <x-button type="submit" variant="danger" size="sm" icon="trash" confirm="Hapus mata pelajaran ini?">Hapus mata pelajaran</x-button>
                    </form>
                @else
                    @if ($subject->assignments_count > 0)<p class="mt-5 border-t border-slate-100 pt-5 text-xs text-slate-500">Mata pelajaran tidak dapat dihapus karena sudah dipakai pada penugasan kelas.</p>@endif
                @endcan
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Preview format</p><h3 class="mt-1 text-lg font-semibold text-slate-900">Header leger</h3></div><span class="text-xs text-slate-500">{{ $subject->short_name }} · urutan {{ $subject->display_order }}</span></div>
                <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full text-left text-xs">
                        <thead class="bg-slate-100 text-[10px] font-semibold uppercase tracking-wide text-slate-600"><tr><th class="whitespace-nowrap border-r border-slate-200 px-3 py-3">No.</th><th class="whitespace-nowrap border-r border-slate-200 px-3 py-3">NIPD</th><th class="whitespace-nowrap border-r border-slate-200 px-3 py-3">Nama peserta didik</th><th class="whitespace-nowrap border-r border-slate-200 px-3 py-3">{{ $subject->short_name }}</th><th class="whitespace-nowrap border-r border-slate-200 px-3 py-3">Predikat</th><th class="whitespace-nowrap px-3 py-3">Deskripsi</th></tr></thead>
                        <tbody><tr class="text-slate-400"><td class="border-r border-t border-slate-200 px-3 py-4">1</td><td class="border-r border-t border-slate-200 px-3 py-4">0001</td><td class="border-r border-t border-slate-200 px-3 py-4">Contoh peserta didik</td><td class="border-r border-t border-slate-200 px-3 py-4">—</td><td class="border-r border-t border-slate-200 px-3 py-4">—</td><td class="border-t border-slate-200 px-3 py-4">Deskripsi capaian kompetensi…</td></tr></tbody>
                    </table>
                </div>
                <p class="mt-4 text-xs text-slate-500">Singkatan wajib diisi karena digunakan sebagai header ringkas pada leger dan rekap nilai.</p>
            </section>
        </div>
    </div>
@endsection
