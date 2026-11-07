@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-page-header eyebrow="Penugasan mata pelajaran" title="Salin konfigurasi mapel" description="Tujuan: {{ $targetClass->name }} · {{ $targetClass->academicYear->name }}. Penugasan yang sudah ada akan diperbarui jika belum dipakai pada rapor terkunci." back-href="{{ route('admin.academic-classes.subject-assignments.index', $targetClass) }}" back-text="Kembali ke penugasan mapel" />
        <form method="POST" action="{{ route('admin.academic-classes.subject-assignments.copy-store', $targetClass) }}" class="space-y-6">@csrf
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><x-select name="source_class_id" label="Kelas sumber" :selected="old('source_class_id')" placeholder="Pilih kelas sumber" required>@foreach ($sourceClasses as $class)<option value="{{ $class->id }}">{{ $class->name }} · {{ $class->program->name }} · {{ $class->academicYear->name }}</option>@endforeach</x-select>@error('source_class_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror<p class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">Penugasan tambahan pada kelas tujuan tidak dihapus. Penugasan yang sama akan diperbarui agar riwayat tetap aman.</p></section>
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.academic-classes.subject-assignments.index', $targetClass) }}" variant="cancel" icon="x">Batal</x-button><x-button type="submit" icon="copy">Salin konfigurasi</x-button></div>
        </form>
    </div>
@endsection
