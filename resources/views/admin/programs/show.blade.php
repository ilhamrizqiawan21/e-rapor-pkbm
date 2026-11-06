@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Program {{ $program->code }}</p><h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $program->name }}</h2><p class="mt-2 text-sm text-slate-600">Kelola nama tingkat yang akan dipakai pada kelas dan rapor.</p></div>
            <div class="flex gap-2"><x-button href="{{ route('admin.programs.index') }}" variant="secondary">Semua program</x-button>@can('update', $program)<x-button href="{{ route('admin.programs.edit', $program) }}">Edit program</x-button>@endcan</div>
        </div>
        @if (session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>@endif
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><div><h3 class="font-semibold text-slate-900">Daftar tingkat</h3><p class="mt-1 text-xs text-slate-500">Nomor tingkat 1–12; nama dapat disesuaikan.</p></div>@can('update', $program)<x-button href="{{ route('admin.programs.levels.create', $program) }}" size="sm">Tambah tingkat</x-button>@endcan</div>
            <div class="divide-y divide-slate-100">
                @forelse ($program->levels as $level)
                    <div class="flex items-center justify-between px-6 py-4"><div><span class="font-semibold text-slate-900">Tingkat {{ $level->number }}</span><span class="ml-3 text-slate-600">{{ $level->name }}</span></div><div class="flex gap-2"><x-button href="{{ route('admin.programs.levels.show', [$program, $level]) }}" variant="secondary" size="sm">Detail</x-button>@can('update', $level)<x-button href="{{ route('admin.programs.levels.edit', [$program, $level]) }}" variant="ghost" size="sm">Edit</x-button>@endcan</div></div>
                @empty
                    <p class="px-6 py-12 text-center text-slate-500">Belum ada tingkat pada program ini.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
