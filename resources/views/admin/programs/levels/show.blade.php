@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6"><x-page-header eyebrow="{{ $program->name }}" title="Tingkat {{ $level->number }}" back-href="{{ route('admin.programs.levels.index', $program) }}" back-text="Kembali ke daftar tingkat" /> <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><dl class="grid gap-5 sm:grid-cols-2"><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Program</dt><dd class="mt-1 text-slate-900">{{ $program->name }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama tingkat</dt><dd class="mt-1 text-slate-900">{{ $level->name }}</dd></div></dl><div class="mt-6 flex justify-end gap-3">@can('update', $level)<x-button href="{{ route('admin.programs.levels.edit', [$program, $level]) }}">Edit tingkat</x-button>@endcan</div></section></div>
@endsection
