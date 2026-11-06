@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-page-header eyebrow="Master data" title="Edit program" back-href="{{ route('admin.programs.show', $program) }}" back-text="Kembali ke detail program" />
        <form method="POST" action="{{ route('admin.programs.update', $program) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf @method('PUT')
            @include('admin.programs._form')
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.programs.show', $program) }}" variant="cancel" icon="x">Batal</x-button><x-button type="submit" icon="save">Simpan perubahan</x-button></div>
        </form>
    </div>
@endsection
