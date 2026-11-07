@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="Konfigurasi rapor" title="Edit rentang {{ $range->code }}" description="Perubahan konfigurasi hanya berlaku untuk rapor baru." back-href="{{ route('admin.predicate-ranges.index') }}" back-text="Kembali ke rentang predikat" />
        <form method="POST" action="{{ route('admin.predicate-ranges.update', $range) }}" class="space-y-5">@method('PUT') @include('admin.predicate-ranges._form')</form>
    </div>
@endsection
