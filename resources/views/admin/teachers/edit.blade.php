@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <a href="{{ route('admin.teachers.show', $teacher) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-950 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 18-6-6 6-6" /></svg>Kembali ke detail Guru</a>
            <p class="mt-5 text-xs font-bold uppercase tracking-[0.22em] text-accent-700">Guru / perbarui akun</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Edit data Guru</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Perbarui identitas, kontak, jabatan, atau status akses untuk {{ $teacher->name }}.</p>
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
