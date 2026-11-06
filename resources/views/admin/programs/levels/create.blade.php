@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl"><div class="mb-6"><p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">{{ $program->name }}</p><h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Tambah tingkat</h2></div><form method="POST" action="{{ route('admin.programs.levels.store', $program) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">@csrf @include('admin.programs.levels._form')<div class="flex justify-end gap-3"><x-button href="{{ route('admin.programs.show', $program) }}" variant="secondary">Batal</x-button><x-button type="submit">Simpan tingkat</x-button></div></form></div>
@endsection
