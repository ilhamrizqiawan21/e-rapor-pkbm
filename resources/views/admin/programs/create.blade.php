@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-page-header eyebrow="Data utama" title="Tambah program" back-href="{{ route('admin.programs.index') }}" back-text="Kembali ke program pendidikan" />
        <form method="POST" action="{{ route('admin.programs.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf
            @include('admin.programs._form')
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.programs.index') }}" variant="cancel" icon="x">Batal</x-button><x-button type="submit" icon="save">Simpan program</x-button></div>
        </form>
    </div>
@endsection
