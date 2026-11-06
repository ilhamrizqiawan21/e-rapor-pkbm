@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="Guru / akun baru" title="Tambah Guru" description="Buat akun Guru yang terikat pada institusi ini. Setelah akun aktif, Guru dapat menerima penugasan mapel dan wali kelas." back-href="{{ route('admin.teachers.index') }}" back-text="Kembali ke direktori Guru" />

        <form method="POST" action="{{ route('admin.teachers.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" x-data="{ isSubmitting: false }" @submit="isSubmitting = true" :aria-busy="isSubmitting.toString()">
            @csrf
            @include('admin.teachers.form')
            <div class="flex justify-end gap-3">
                <x-button href="{{ route('admin.teachers.index') }}" variant="cancel" icon="x">Batal</x-button>
                <x-button type="submit" icon="save" x-bind:disabled="isSubmitting"><span x-show="!isSubmitting">Simpan Guru</span><span x-cloak x-show="isSubmitting">Memproses…</span></x-button>
            </div>
        </form>
    </div>
@endsection
