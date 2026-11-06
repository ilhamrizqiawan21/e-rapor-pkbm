@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl"><div class="mb-6"><p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Penempatan awal</p><h2 class="mt-2 text-3xl font-semibold text-slate-950">Tambah peserta didik</h2><p class="mt-2 text-sm text-slate-600">{{ $academicClass->name }} · {{ $academicClass->academicYear->name }}</p></div>
        <form method="POST" action="{{ route('admin.academic-classes.enrollments.store', $academicClass) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@csrf
            <div><label class="text-sm font-medium text-slate-700" for="student_id">Peserta didik</label><select id="student_id" name="student_id" required class="mt-2 w-full rounded-xl border-slate-300">@foreach ($students as $student)<option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->student_number }} — {{ $student->full_name }}</option>@endforeach</select>@error('student_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-medium text-slate-700" for="start_date">Tanggal mulai</label><input id="start_date" name="start_date" type="date" value="{{ old('start_date', $enrollment->start_date?->format('Y-m-d')) }}" required class="mt-2 w-full rounded-xl border-slate-300">@error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-medium text-slate-700" for="notes">Catatan</label><textarea id="notes" name="notes" rows="3" class="mt-2 w-full rounded-xl border-slate-300">{{ old('notes') }}</textarea>@error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div class="flex justify-end gap-2"><x-button variant="secondary" href="{{ route('admin.academic-classes.enrollments.index', $academicClass) }}">Batal</x-button><x-button type="submit">Simpan penempatan</x-button></div>
        </form>
    </div>
@endsection
