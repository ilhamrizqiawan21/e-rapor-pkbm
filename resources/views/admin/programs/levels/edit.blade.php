@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl"><x-page-header eyebrow="{{ $program->name }}" title="Edit tingkat" back-href="{{ route('admin.programs.levels.show', [$program, $level]) }}" back-text="Kembali ke detail tingkat" /><form method="POST" action="{{ route('admin.programs.levels.update', [$program, $level]) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">@csrf @method('PUT') @include('admin.programs.levels._form')<div class="flex justify-end gap-3"><x-button href="{{ route('admin.programs.show', $program) }}" variant="secondary">Batal</x-button><x-button type="submit">Simpan perubahan</x-button></div></form></div>
@endsection
