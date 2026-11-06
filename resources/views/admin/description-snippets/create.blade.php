@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Bank deskripsi</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Tambah snippet</h2>
            <p class="mt-2 text-sm text-slate-600">Buat kalimat reusable untuk membantu pengisian deskripsi capaian.</p>
        </div>
        <form method="POST" action="{{ route('admin.description-snippets.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf
            @include('admin.description-snippets._form')
            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <x-button href="{{ route('admin.description-snippets.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit">Simpan snippet</x-button>
            </div>
        </form>
    </div>
@endsection
