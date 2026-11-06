@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-4xl"><x-page-header eyebrow="Master data" title="Tambah Tahun Pelajaran" back-href="{{ route('admin.academic-years.index') }}" back-text="Kembali ke tahun pelajaran" />@include('admin.academic-years.form')</div>
@endsection
