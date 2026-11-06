@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Bank deskripsi</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $snippet->title }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $snippet->subject?->name ?? 'Semua mata pelajaran' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-button href="{{ route('admin.description-snippets.index') }}" variant="secondary">Kembali ke bank</x-button>
                @can('update', $snippet)
                    <x-button href="{{ route('admin.description-snippets.edit', $snippet) }}">Edit</x-button>
                @endcan
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Isi reusable</p>
                <div class="mt-4 whitespace-pre-line rounded-xl border border-slate-100 bg-slate-50 p-5 text-sm leading-7 text-slate-800">{{ $snippet->content }}</div>
                <p class="mt-4 text-xs text-slate-500">Saat digunakan pada input nilai, teks ini disalin ke deskripsi peserta didik dan dapat diedit tanpa mengubah snippet asli.</p>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Informasi snippet</h3>
                <dl class="mt-4 divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Visibilitas</dt><dd><x-badge :variant="$snippet->isShared() ? 'info' : 'brand'">{{ $snippet->isShared() ? 'Dibagikan' : 'Pribadi' }}</x-badge></dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Guru pemilik</dt><dd class="text-right text-slate-900">{{ $snippet->owner?->name ?? 'Tidak ditentukan' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Mapel</dt><dd class="text-right text-slate-900">{{ $snippet->subject?->name ?? 'Semua mapel' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Status</dt><dd><x-badge :variant="$snippet->active ? 'ACTIVE' : 'INACTIVE'" dot>{{ $snippet->active ? 'Aktif' : 'Nonaktif' }}</x-badge></dd></div>
                </dl>
                @can('delete', $snippet)
                    <form method="POST" action="{{ route('admin.description-snippets.destroy', $snippet) }}" class="mt-5 border-t border-slate-100 pt-5" onsubmit="return confirm('Hapus snippet ini?')">
                        @csrf @method('DELETE')
                        <x-button type="submit" variant="danger" size="sm">Hapus snippet</x-button>
                    </form>
                @endcan
            </section>
        </div>
    </div>
@endsection
