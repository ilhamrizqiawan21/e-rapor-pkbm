@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Mapping mata pelajaran</p><h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Salin konfigurasi mapel</h2><p class="mt-2 text-sm text-slate-600">Tujuan: {{ $targetClass->name }} · {{ $targetClass->academicYear->name }}. Mapping yang sudah ada akan diperbarui jika belum dipakai pada rapor terkunci.</p></div>
        <form method="POST" action="{{ route('admin.academic-classes.subject-assignments.copy-store', $targetClass) }}" class="space-y-6">@csrf
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><x-select name="source_class_id" label="Kelas sumber" :selected="old('source_class_id')" placeholder="Pilih kelas sumber" required>@foreach ($sourceClasses as $class)<option value="{{ $class->id }}">{{ $class->name }} · {{ $class->program->name }} · {{ $class->academicYear->name }}</option>@endforeach</x-select>@error('source_class_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror<p class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">Mapping tambahan pada kelas tujuan tidak dihapus. Proses ini bersifat upsert agar histori tetap aman.</p></section>
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.academic-classes.subject-assignments.index', $targetClass) }}" variant="secondary">Batal</x-button><x-button type="submit">Salin konfigurasi</x-button></div>
        </form>
    </div>
@endsection
