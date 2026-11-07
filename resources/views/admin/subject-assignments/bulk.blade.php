@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl">
        <x-page-header eyebrow="Penugasan mata pelajaran" title="Tambah mapel ke beberapa kelas" description="Mapel yang sudah ada pada kelas akan dilewati. KKM dan guru berikut digunakan untuk penugasan baru." back-href="{{ route('admin.academic-classes.index') }}" back-text="Kembali ke kelas / rombel" />
        <form method="POST" action="{{ route('admin.academic-classes.subject-assignments.bulk-store') }}" class="space-y-6">@csrf
            <section class="grid gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2">
                <x-select name="class_ids[]" id="class_ids" label="Kelas tujuan" class="sm:col-span-2" multiple required :error="$errors->first('class_ids')">
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected(in_array($class->id, old('class_ids', [])))>{{ $class->name }} · {{ $class->program->name }} · {{ $class->academicYear->name }}</option>
                    @endforeach
                </x-select>
                <x-select name="subject_ids[]" id="subject_ids" label="Mata pelajaran" class="sm:col-span-2" multiple required :error="$errors->first('subject_ids')">
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected(in_array($subject->id, old('subject_ids', [])))>{{ $subject->name }} · {{ $subject->code }}</option>
                    @endforeach
                </x-select>
                <x-select name="teacher_id" label="Guru pengampu awal" :selected="old('teacher_id')" placeholder="Pilih guru" required>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}{{ $teacher->staffProfile?->employee_number ? ' · '.$teacher->staffProfile->employee_number : '' }}</option>@endforeach</x-select>
                <x-input name="minimum_score" type="number" min="0" max="100" label="KKM" :value="old('minimum_score', 70)" required />
            </section>
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.academic-classes.index') }}" variant="cancel" icon="x">Batal</x-button><x-button type="submit" icon="add">Tambah penugasan</x-button></div>
        </form>
    </div>
@endsection
