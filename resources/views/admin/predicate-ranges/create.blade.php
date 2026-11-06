@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="Konfigurasi rapor" title="Tambah rentang predikat" description="Nilai minimum dan maksimum harus berada pada 0–100 serta tidak boleh overlap." back-href="{{ route('admin.predicate-ranges.index') }}" back-text="Kembali ke rentang predikat" />
        <form method="POST" action="{{ route('admin.predicate-ranges.store') }}" class="space-y-5">@include('admin.predicate-ranges._form')</form>
    </div>
@endsection
