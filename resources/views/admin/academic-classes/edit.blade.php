@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="Pengaturan akademik" title="Edit kelas / rombel" description="Perubahan kelas ditolak ketika tahun pelajaran sudah ditutup." back-href="{{ route('admin.academic-classes.show', $academicClass) }}" back-text="Kembali ke detail kelas" />
        <form method="POST" action="{{ route('admin.academic-classes.update', $academicClass) }}" class="space-y-6">
            @method('PUT')
            @include('admin.academic-classes.form')
        </form>
    </div>
@endsection
