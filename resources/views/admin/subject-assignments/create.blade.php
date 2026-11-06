@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">{{ $academicClass->name }}</p><h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Tambah penugasan mapel</h2><p class="mt-2 text-sm text-slate-600">Tentukan mata pelajaran, guru pengampu, KKM, dan urutan tampil pada rapor.</p></div>
        <form method="POST" action="{{ route('admin.academic-classes.subject-assignments.store', $academicClass) }}" class="space-y-6">
            @include('admin.subject-assignments.form')
        </form>
    </div>
@endsection
