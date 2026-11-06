@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Bank deskripsi</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Edit snippet</h2>
            <p class="mt-2 text-sm text-slate-600">Perubahan hanya berlaku untuk snippet ini dan tidak mengubah deskripsi yang sudah tersimpan pada rapor.</p>
        </div>
        <form method="POST" action="{{ route('admin.description-snippets.update', $snippet) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf @method('PUT')
            @include('admin.description-snippets._form')
            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <x-button href="{{ route('admin.description-snippets.show', $snippet) }}" variant="secondary">Batal</x-button>
                <x-button type="submit">Simpan perubahan</x-button>
            </div>
        </form>
    </div>
@endsection
