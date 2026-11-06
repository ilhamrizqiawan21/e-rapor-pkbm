@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master SDM</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Tambah Guru</h2>
            <p class="mt-2 text-sm text-slate-600">Buat akun Guru yang terikat pada institusi Anda.</p>
        </div>

        <form method="POST" action="{{ route('admin.teachers.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" x-data="{ isSubmitting: false }" @submit="isSubmitting = true" :aria-busy="isSubmitting.toString()">
            @csrf
            @include('admin.teachers.form')
            <div class="flex justify-end gap-3">
                <x-button href="{{ route('admin.teachers.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" x-bind:disabled="isSubmitting"><span x-show="!isSubmitting">Simpan Guru</span><span x-cloak x-show="isSubmitting">Memproses…</span></x-button>
            </div>
        </form>
    </div>
@endsection
