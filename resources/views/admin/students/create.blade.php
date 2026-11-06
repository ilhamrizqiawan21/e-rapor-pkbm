@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master peserta didik</p><h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Tambah peserta didik</h2><p class="mt-2 text-sm text-slate-600">Tambahkan identitas peserta didik ke institusi Anda.</p></div>
        <form method="POST" action="{{ route('admin.students.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf
            @include('admin.students.form')
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.students.index') }}" variant="secondary">Batal</x-button><x-button type="submit">Simpan peserta didik</x-button></div>
        </form>
    </div>
@endsection
