@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master peserta didik</p><h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Edit peserta didik</h2><p class="mt-2 text-sm text-slate-600">Perbarui identitas dan status {{ $student->full_name }}.</p></div>
        <form method="POST" action="{{ route('admin.students.update', $student) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf
            @method('PUT')
            @include('admin.students.form')
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.students.show', $student) }}" variant="secondary">Batal</x-button><x-button type="submit">Simpan perubahan</x-button></div>
        </form>
    </div>
@endsection
