@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl">
        <x-page-header eyebrow="Mapping mata pelajaran" title="Tambah mapel ke beberapa kelas" description="Mapel yang sudah ada pada kelas akan dilewati. KKM dan guru berikut digunakan untuk mapping baru." back-href="{{ route('admin.academic-classes.index') }}" back-text="Kembali ke kelas / rombel" />
        <form method="POST" action="{{ route('admin.academic-classes.subject-assignments.bulk-store') }}" class="space-y-6">@csrf
            <section class="grid gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="text-sm font-medium text-slate-700" for="class_ids">Kelas tujuan</label><select id="class_ids" name="class_ids[]" multiple required class="mt-2 min-h-40 w-full rounded-xl border-slate-300">@foreach ($classes as $class)<option value="{{ $class->id }}" @selected(in_array($class->id, old('class_ids', [])))>{{ $class->name }} · {{ $class->program->name }} · {{ $class->academicYear->name }}</option>@endforeach</select>@error('class_ids')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="text-sm font-medium text-slate-700" for="subject_ids">Mata pelajaran</label><select id="subject_ids" name="subject_ids[]" multiple required class="mt-2 min-h-40 w-full rounded-xl border-slate-300">@foreach ($subjects as $subject)<option value="{{ $subject->id }}" @selected(in_array($subject->id, old('subject_ids', [])))>{{ $subject->name }} · {{ $subject->code }}</option>@endforeach</select>@error('subject_ids')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <x-select name="teacher_id" label="Guru pengampu awal" :selected="old('teacher_id')" placeholder="Pilih guru" required>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}{{ $teacher->staffProfile?->employee_number ? ' · '.$teacher->staffProfile->employee_number : '' }}</option>@endforeach</x-select>
                <x-input name="minimum_score" type="number" min="0" max="100" label="KKM" :value="old('minimum_score', 70)" required />
            </section>
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.academic-classes.index') }}" variant="cancel" icon="x">Batal</x-button><x-button type="submit" icon="add">Tambah mapping</x-button></div>
        </form>
    </div>
@endsection
