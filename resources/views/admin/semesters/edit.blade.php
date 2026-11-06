@extends('layouts.app')
@section('content')<div class="mx-auto max-w-4xl"><x-page-header eyebrow="Master data" title="Edit Semester" back-href="{{ route('admin.academic-years.show', $academicYear) }}" back-text="Kembali ke tahun pelajaran" />@include('admin.semesters.form')</div>@endsection
