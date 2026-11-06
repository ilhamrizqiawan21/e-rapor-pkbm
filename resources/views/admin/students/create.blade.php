@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="Master peserta didik" title="Tambah peserta didik" description="Tambahkan identitas peserta didik ke institusi Anda." back-href="{{ route('admin.students.index') }}" back-text="Kembali ke peserta didik" />
        <form method="POST" action="{{ route('admin.students.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf
            @include('admin.students.form')
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.students.index') }}" variant="cancel" icon="x">Batal</x-button><x-button type="submit" icon="save">Simpan peserta didik</x-button></div>
        </form>
    </div>
@endsection
