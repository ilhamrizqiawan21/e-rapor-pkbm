@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl">
        <x-page-header eyebrow="Penugasan mata pelajaran" title="Tetapkan guru sekaligus" description="Pilih beberapa penugasan untuk mengubah guru pengampu sekaligus." back-href="{{ route('admin.academic-classes.index') }}" back-text="Kembali ke kelas / rombel" />
        <form method="POST" action="{{ route('admin.academic-classes.subject-assignments.bulk-assign-teacher') }}" class="space-y-6">@csrf
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><x-table.th>Pilih</x-table.th><x-table.th>Kelas</x-table.th><x-table.th>Mapel</x-table.th><x-table.th>Guru sekarang</x-table.th><x-table.th>KKM</x-table.th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($assignments as $assignment)<tr><x-table.td><input type="checkbox" name="assignment_ids[]" value="{{ $assignment->id }}" class="rounded border-slate-300"></x-table.td><x-table.td>{{ $assignment->academicClass->name }} · {{ $assignment->academicClass->academicYear->name }}</x-table.td><x-table.td>{{ $assignment->subject->name }}</x-table.td><x-table.td>{{ $assignment->teacher->name }}</x-table.td><x-table.td>{{ $assignment->minimum_score }}</x-table.td></tr>@empty<tr><x-table.td colspan="5">Belum ada penugasan pada periode terbuka.</x-table.td></tr>@endforelse</tbody></table></div></section>
            <section class="flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><x-select name="teacher_id" label="Guru pengampu baru" :selected="old('teacher_id')" placeholder="Pilih guru" required>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</x-select><x-button type="submit">Terapkan guru</x-button></section>
        </form>
    </div>
@endsection
