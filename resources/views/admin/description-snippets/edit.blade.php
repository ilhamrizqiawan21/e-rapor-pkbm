@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-page-header eyebrow="Bank deskripsi" title="Edit template deskripsi" description="Perubahan hanya berlaku untuk template ini dan tidak mengubah deskripsi yang sudah tersimpan pada rapor." back-href="{{ route('admin.description-snippets.show', $snippet) }}" back-text="Kembali ke detail template" />
        <form method="POST" action="{{ route('admin.description-snippets.update', $snippet) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf @method('PUT')
            @include('admin.description-snippets._form')
            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <x-button href="{{ route('admin.description-snippets.show', $snippet) }}" variant="cancel" icon="x">Batal</x-button>
                <x-button type="submit" icon="save">Simpan perubahan</x-button>
            </div>
        </form>
    </div>
@endsection
