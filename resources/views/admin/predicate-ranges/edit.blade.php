@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Konfigurasi rapor</p><h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Edit rentang {{ $range->code }}</h2><p class="mt-2 text-sm text-slate-600">Perubahan konfigurasi hanya berlaku untuk snapshot rapor baru.</p></div>
        <form method="POST" action="{{ route('admin.predicate-ranges.update', $range) }}" class="space-y-5">@method('PUT') @include('admin.predicate-ranges._form')</form>
    </div>
@endsection
