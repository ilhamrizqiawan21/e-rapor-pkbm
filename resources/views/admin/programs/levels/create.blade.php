@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl"><x-page-header eyebrow="{{ $program->name }}" title="Tambah tingkat" back-href="{{ route('admin.programs.show', $program) }}" back-text="Kembali ke detail program" /><form method="POST" action="{{ route('admin.programs.levels.store', $program) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">@csrf @include('admin.programs.levels._form')<div class="flex justify-end gap-3"><x-button href="{{ route('admin.programs.show', $program) }}" variant="secondary">Batal</x-button><x-button type="submit">Simpan tingkat</x-button></div></form></div>
@endsection
