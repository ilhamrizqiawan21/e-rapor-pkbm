@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-header eyebrow="Bank deskripsi" title="{{ $snippet->title }}" description="{{ $snippet->subject?->name ?? 'Semua mata pelajaran' }}" back-href="{{ route('admin.description-snippets.index') }}" back-text="Kembali ke bank deskripsi">
            @can('update', $snippet)<x-button href="{{ route('admin.description-snippets.edit', $snippet) }}" variant="edit" icon="edit">Edit</x-button>@endcan
        </x-page-header>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Materi reusable</p>
                <div class="mt-4 whitespace-pre-line rounded-xl border border-slate-100 bg-slate-50 p-5 text-sm leading-7 text-slate-800">{{ $snippet->material ?: $snippet->content }}</div>
                <p class="mt-4 text-xs text-slate-500">Saat digunakan pada input nilai, materi ini akan dirangkai dengan template predikat lalu tetap dapat disunting untuk peserta didik tertentu.</p>
                @if ($snippet->templates->isNotEmpty())
                    <div class="mt-6 space-y-3">
                        <h3 class="text-sm font-semibold text-slate-900">Template predikat</h3>
                        @foreach ($snippet->templates as $template)
                            <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center justify-between gap-3"><span class="font-semibold text-slate-900">{{ $template->predicate_code }} · {{ $template->predicate_label }}</span><span class="text-xs text-slate-500">{{ $template->active ? 'Aktif' : 'Nonaktif' }}</span></div><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $template->template }}</p></div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Informasi template</h3>
                <dl class="mt-4 divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Visibilitas</dt><dd><x-badge :variant="$snippet->isShared() ? 'info' : 'brand'">{{ $snippet->isShared() ? 'Dibagikan' : 'Pribadi' }}</x-badge></dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Guru pemilik</dt><dd class="text-right text-slate-900">{{ $snippet->owner?->name ?? 'Tidak ditentukan' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Mapel</dt><dd class="text-right text-slate-900">{{ $snippet->subject?->name ?? 'Semua mapel' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Status</dt><dd><x-badge :variant="$snippet->active ? 'ACTIVE' : 'INACTIVE'" dot>{{ $snippet->active ? 'Aktif' : 'Nonaktif' }}</x-badge></dd></div>
                </dl>
                @can('delete', $snippet)
                    <form method="POST" action="{{ route('admin.description-snippets.destroy', $snippet) }}" class="mt-5 border-t border-slate-100 pt-5">
                        @csrf @method('DELETE')
                        <x-button type="submit" variant="danger" size="sm" icon="trash" confirm="Hapus template deskripsi ini?">Hapus template</x-button>
                    </form>
                @endcan
            </section>
        </div>
    </div>
@endsection
