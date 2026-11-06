@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master SDM</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Edit data Guru</h2>
            <p class="mt-2 text-sm text-slate-600">Perbarui identitas, kontak, jabatan, atau status akun {{ $teacher->name }}.</p>
        </div>

        <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" x-data="{ isSubmitting: false }" @submit="isSubmitting = true" :aria-busy="isSubmitting.toString()">
            @csrf
            @method('PUT')
            @include('admin.teachers.form')
            <div class="flex flex-wrap justify-end gap-3">
                <x-button href="{{ route('admin.users.password.edit', $teacher) }}" variant="warning">Reset password</x-button>
                <x-button href="{{ route('admin.teachers.show', $teacher) }}" variant="secondary">Batal</x-button>
                <x-button type="submit" x-bind:disabled="isSubmitting"><span x-show="!isSubmitting">Simpan perubahan</span><span x-cloak x-show="isSubmitting">Memproses…</span></x-button>
            </div>
        </form>
    </div>
@endsection
