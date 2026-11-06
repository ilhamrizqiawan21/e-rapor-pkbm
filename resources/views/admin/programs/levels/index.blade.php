@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-header eyebrow="{{ $program->name }}" title="Daftar tingkat" back-href="{{ route('admin.programs.show', $program) }}" back-text="Kembali ke detail program" />
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex justify-between"><p class="text-sm text-slate-600">Pengelolaan tingkat 1 sampai 12.</p>@can('update', $program)<x-button href="{{ route('admin.programs.levels.create', $program) }}" size="sm">Tambah tingkat</x-button>@endcan</div>
            <div class="grid gap-3 sm:grid-cols-2">
                @forelse ($levels as $level)<a href="{{ route('admin.programs.levels.show', [$program, $level]) }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-300 hover:bg-brand-50"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tingkat {{ $level->number }}</span><p class="mt-1 font-semibold text-slate-900">{{ $level->name }}</p></a>@empty<p class="col-span-2 py-10 text-center text-slate-500">Belum ada tingkat.</p>@endforelse
            </div>
            <x-pagination :paginator="$levels" />
        </section>
    </div>
@endsection
