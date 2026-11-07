@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl">
        <x-page-header eyebrow="Penugasan mata pelajaran" title="Tambah mapel ke beberapa kelas" description="Mapel yang sudah ada pada kelas akan dilewati. KKM dan guru berikut digunakan untuk penugasan baru." back-href="{{ route('admin.academic-classes.index') }}" back-text="Kembali ke kelas / rombel" />
        <form method="POST" action="{{ route('admin.academic-classes.subject-assignments.bulk-store') }}" class="space-y-6">@csrf
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <h2 class="text-lg font-semibold text-slate-950">Pilih kelas dan mata pelajaran</h2>
                <p class="mt-1 text-sm text-slate-500">Centang satu atau beberapa pilihan pada masing-masing daftar.</p>
                <div class="mt-5 grid gap-5 md:grid-cols-2">
                <fieldset class="min-w-0" x-data="{ selected: @js(array_map('strval', (array) old('class_ids', []))) }">
                    <legend class="text-sm font-semibold text-slate-800">Kelas tujuan <span class="text-rose-500">*</span></legend>
                    <p class="my-2 text-xs text-slate-500" x-text="selected.length + ' dipilih'"></p>
                    <div class="max-h-64 space-y-1 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2">
                        @forelse ($classes as $class)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg px-3 py-2.5 text-sm hover:bg-white"><input type="checkbox" name="class_ids[]" value="{{ $class->id }}" x-model="selected" class="mt-1 rounded border-slate-300 text-brand-700 focus:ring-brand-500"><span><span class="block font-medium text-slate-900">{{ $class->name }}</span><span class="text-xs text-slate-500">{{ $class->program->name }} · {{ $class->academicYear->name }}</span></span></label>
                        @empty
                            <p class="p-3 text-sm text-slate-500">Belum ada kelas tersedia.</p>
                        @endforelse
                    </div>
                    @error('class_ids')<p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                    @error('class_ids.*')<p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                </fieldset>
                <fieldset class="min-w-0" x-data="{ selected: @js(array_map('strval', (array) old('subject_ids', []))) }">
                    <legend class="text-sm font-semibold text-slate-800">Mata pelajaran <span class="text-rose-500">*</span></legend>
                    <p class="my-2 text-xs text-slate-500" x-text="selected.length + ' dipilih'"></p>
                    <div class="max-h-64 space-y-1 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2">
                        @forelse ($subjects as $subject)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg px-3 py-2.5 text-sm hover:bg-white"><input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}" x-model="selected" class="mt-1 rounded border-slate-300 text-brand-700 focus:ring-brand-500"><span><span class="block font-medium text-slate-900">{{ $subject->name }}</span><span class="text-xs text-slate-500">{{ $subject->code }}</span></span></label>
                        @empty
                            <p class="p-3 text-sm text-slate-500">Belum ada mata pelajaran tersedia.</p>
                        @endforelse
                    </div>
                    @error('subject_ids')<p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                    @error('subject_ids.*')<p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                </fieldset>
                </div>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <h2 class="text-lg font-semibold text-slate-950">Pengaturan penugasan</h2>
                <p class="mt-1 text-sm text-slate-500">Guru dan KKM berlaku untuk setiap penugasan baru.</p>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-select name="teacher_id" label="Guru pengampu awal" :selected="old('teacher_id')" placeholder="Pilih guru" required>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}{{ $teacher->staffProfile?->employee_number ? ' · '.$teacher->staffProfile->employee_number : '' }}</option>@endforeach</x-select>
                <x-input name="minimum_score" type="number" min="0" max="100" label="KKM" :value="old('minimum_score', 70)" required />
                </div>
            </section>
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.academic-classes.index') }}" variant="cancel" icon="x">Batal</x-button><x-button type="submit" icon="add">Tambah penugasan</x-button></div>
        </form>
    </div>
@endsection
