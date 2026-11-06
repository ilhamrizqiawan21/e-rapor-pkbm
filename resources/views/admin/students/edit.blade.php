@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="Master peserta didik" title="Edit peserta didik" description="Perbarui identitas dan status {{ $student->full_name }}." back-href="{{ route('admin.students.show', $student) }}" back-text="Kembali ke detail peserta didik" />
        <form method="POST" action="{{ route('admin.students.update', $student) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf
            @method('PUT')
            @include('admin.students.form')
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.students.show', $student) }}" variant="secondary">Batal</x-button><x-button type="submit">Simpan perubahan</x-button></div>
        </form>
    </div>
@endsection
