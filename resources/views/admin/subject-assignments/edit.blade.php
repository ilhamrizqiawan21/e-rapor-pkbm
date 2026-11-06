@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="{{ $academicClass->name }}" title="Edit penugasan mapel" description="Penugasan dengan nilai pada rapor terkunci tidak dapat diubah." back-href="{{ route('admin.academic-classes.subject-assignments.index', $academicClass) }}" back-text="Kembali ke penugasan mapel" />
        <form method="POST" action="{{ route('admin.academic-classes.subject-assignments.update', [$academicClass, $assignment]) }}" class="space-y-6">
            @method('PUT')
            @include('admin.subject-assignments.form')
        </form>
    </div>
@endsection
