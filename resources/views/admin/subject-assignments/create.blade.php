@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="{{ $academicClass->name }}" title="Tambah penugasan mapel" description="Tentukan mata pelajaran, guru pengampu, KKM, dan urutan tampil pada rapor." back-href="{{ route('admin.academic-classes.subject-assignments.index', $academicClass) }}" back-text="Kembali ke penugasan mapel" />
        <form method="POST" action="{{ route('admin.academic-classes.subject-assignments.store', $academicClass) }}" class="space-y-6">
            @include('admin.subject-assignments.form')
        </form>
    </div>
@endsection
