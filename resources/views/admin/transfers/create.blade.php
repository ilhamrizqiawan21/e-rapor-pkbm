@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl"><x-page-header eyebrow="Persetujuan Admin" title="Pindah kelas" description="{{ $enrollment->student->full_name }} · {{ $enrollment->academicClass->name }} · {{ $enrollment->academicClass->academicYear->name }}" back-href="{{ route('admin.enrollments.show', $enrollment) }}" back-text="Kembali ke penempatan" />
        <form method="POST" action="{{ route('admin.enrollments.transfers.store', $enrollment) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@csrf
            <x-select name="target_class_id" id="target_class_id" label="Kelas tujuan" :selected="old('target_class_id')" :error="$errors->first('target_class_id')" required>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected(old('target_class_id') == $class->id)>{{ $class->name }} · {{ $class->program->name }} · {{ $class->level->name }}</option>
                @endforeach
            </x-select>
            <div><label class="text-sm font-medium text-slate-700" for="effective_date">Tanggal efektif</label><input id="effective_date" name="effective_date" type="date" value="{{ old('effective_date', now()->toDateString()) }}" required class="mt-2 w-full rounded-xl border-slate-300">@error('effective_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-medium text-slate-700" for="reason">Alasan wajib</label><input id="reason" name="reason" value="{{ old('reason') }}" required class="mt-2 w-full rounded-xl border-slate-300">@error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <x-select name="reporting_enrollment_id" id="reporting_enrollment_id" label="Sumber rapor" :selected="old('reporting_enrollment_id', 'target')" :error="$errors->first('reporting_enrollment_id')" hint="Pilihan ini menentukan penempatan yang dipakai sebagai sumber rapor saat pindah di tengah semester.">
                <option value="target">Penempatan tujuan (bawaan)</option>
                <option value="{{ $enrollment->id }}" @selected(old('reporting_enrollment_id') == $enrollment->id)>Penempatan asal</option>
            </x-select>
            <div><label class="text-sm font-medium text-slate-700" for="notes">Catatan</label><textarea id="notes" name="notes" rows="3" class="mt-2 w-full rounded-xl border-slate-300">{{ old('notes') }}</textarea></div>
            <div class="flex justify-end gap-2"><x-button variant="cancel" icon="x" href="{{ route('admin.enrollments.show', $enrollment) }}">Batal</x-button><x-button type="submit" variant="success" icon="check" confirm="Setujui perpindahan peserta didik ini?">Setujui perpindahan</x-button></div>
        </form>
    </div>
@endsection
