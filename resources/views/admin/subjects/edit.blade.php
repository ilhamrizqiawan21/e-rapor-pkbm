@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master data</p><h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Edit mata pelajaran</h2></div>
        <form method="POST" action="{{ route('admin.subjects.update', $subject) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf @method('PUT')
            @include('admin.subjects._form')
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.subjects.show', $subject) }}" variant="secondary">Batal</x-button><x-button type="submit">Simpan perubahan</x-button></div>
        </form>
    </div>
@endsection
