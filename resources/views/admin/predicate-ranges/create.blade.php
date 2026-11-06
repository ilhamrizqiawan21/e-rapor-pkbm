@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Konfigurasi rapor</p><h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Tambah rentang predikat</h2><p class="mt-2 text-sm text-slate-600">Nilai minimum dan maksimum harus berada pada 0–100 serta tidak boleh overlap.</p></div>
        <form method="POST" action="{{ route('admin.predicate-ranges.store') }}" class="space-y-5">@include('admin.predicate-ranges._form')</form>
    </div>
@endsection
