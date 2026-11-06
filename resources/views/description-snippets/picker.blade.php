@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Input nilai</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Pilih deskripsi capaian</h2>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">Gunakan deskripsi tersimpan sebagai draf, lalu sesuaikan kembali pada kolom deskripsi nilai jika diperlukan.</p>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <form method="GET" action="{{ $pickerUrl }}" class="grid gap-4 sm:grid-cols-[1fr_auto_auto] sm:items-end">
                @if ($subject)
                    <input type="hidden" name="subject_id" value="{{ $subject->getKey() }}">
                @endif
                <x-input name="search" label="Cari deskripsi" :value="$search" placeholder="Judul atau isi deskripsi" autocomplete="off" />
                <x-button type="submit" variant="secondary">Cari</x-button>
                <x-button href="{{ $pickerUrl }}{{ $subject ? '?subject_id='.$subject->getKey() : '' }}" variant="ghost">Reset</x-button>
            </form>
            <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span class="rounded-full bg-slate-100 px-3 py-1">{{ $snippets->total() }} deskripsi tersedia</span>
                @if ($subject)
                    <span class="rounded-full bg-accent-50 px-3 py-1 text-accent-800">Mapel: {{ $subject->name }}</span>
                @endif
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-800">Menampilkan milik Anda dan yang dibagikan</span>
            </div>
        </section>

        @include('description-snippets.partials.picker-list', [
            'snippets' => $snippets,
            'selectionTargets' => $selectionTargets,
        ])
    </div>
@endsection
