@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-page-header eyebrow="Master data" title="Edit mata pelajaran" back-href="{{ route('admin.subjects.show', $subject) }}" back-text="Kembali ke detail mata pelajaran" />
        <form method="POST" action="{{ route('admin.subjects.update', $subject) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf @method('PUT')
            @include('admin.subjects._form')
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.subjects.show', $subject) }}" variant="cancel" icon="x">Batal</x-button><x-button type="submit" icon="save">Simpan perubahan</x-button></div>
        </form>
    </div>
@endsection
