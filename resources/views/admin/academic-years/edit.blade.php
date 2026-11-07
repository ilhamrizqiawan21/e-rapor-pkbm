@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-4xl"><x-page-header eyebrow="Data utama" title="Edit Tahun Pelajaran" back-href="{{ route('admin.academic-years.show', $academicYear) }}" back-text="Kembali ke detail tahun pelajaran" />@include('admin.academic-years.form')</div>
@endsection
