@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-page-header eyebrow="Bank deskripsi" title="Tambah snippet" description="Buat kalimat reusable untuk membantu pengisian deskripsi capaian." back-href="{{ route('admin.description-snippets.index') }}" back-text="Kembali ke bank deskripsi" />
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
