@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master akademik</p><h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Tambah kelas / rombel</h2><p class="mt-2 text-sm text-slate-600">Buat kelas untuk satu program dan tingkat pada tahun pelajaran tertentu.</p></div>
        <form method="POST" action="{{ route('admin.academic-classes.store') }}" class="space-y-6">
            @include('admin.academic-classes.form')
        </form>
    </div>
@endsection
