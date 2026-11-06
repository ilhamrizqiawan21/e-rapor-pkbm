@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="Guru / perbarui akun" title="Edit data Guru" description="Perbarui identitas, kontak, jabatan, atau status akses untuk {{ $teacher->name }}." back-href="{{ route('admin.teachers.show', $teacher) }}" back-text="Kembali ke detail Guru" />

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
