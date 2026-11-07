@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="Pengaturan akademik" title="Tambah kelas / rombel" description="Buat kelas untuk satu program dan tingkat pada tahun pelajaran tertentu." back-href="{{ route('admin.academic-classes.index') }}" back-text="Kembali ke kelas / rombel" />
        <form method="POST" action="{{ route('admin.academic-classes.store') }}" class="space-y-6">
            @include('admin.academic-classes.form')
        </form>
    </div>
@endsection
