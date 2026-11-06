@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-page-header eyebrow="Master data" title="Tambah mata pelajaran" back-href="{{ route('admin.subjects.index') }}" back-text="Kembali ke mata pelajaran" />
        <form method="POST" action="{{ route('admin.subjects.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf
            @include('admin.subjects._form')
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.subjects.index') }}" variant="cancel" icon="x">Batal</x-button><x-button type="submit" icon="save">Simpan mata pelajaran</x-button></div>
        </form>
    </div>
@endsection
