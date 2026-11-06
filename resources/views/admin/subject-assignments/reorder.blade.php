@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Mapping mata pelajaran</p><h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Atur urutan mapel</h2><p class="mt-2 text-sm text-slate-600">Urutan menjadi urutan tampil pada input nilai, rapor, dan leger.</p></div>
        <form method="POST" action="{{ route('admin.academic-classes.subject-assignments.reorder', $academicClass) }}" class="space-y-6">@csrf @method('PATCH')
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><ol class="space-y-3">@forelse ($assignments as $assignment)<li class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4"><label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="display_order_{{ $assignment->id }}">Urutan</label><input id="display_order_{{ $assignment->id }}" name="display_orders[{{ $assignment->id }}]" type="number" min="1" max="9999" value="{{ old('display_orders.'.$assignment->id, $loop->iteration) }}" required class="w-20 rounded-lg border-slate-300"><div><p class="font-semibold text-slate-900">{{ $assignment->subject->name }}</p><p class="text-xs text-slate-500">{{ $assignment->teacher->name }} · KKM {{ $assignment->minimum_score }}</p></div></li>@empty<li class="text-sm text-slate-600">Belum ada mapping pada kelas ini.</li>@endforelse</ol></section>
            <div class="flex justify-end gap-3"><x-button href="{{ route('admin.academic-classes.subject-assignments.index', $academicClass) }}" variant="secondary">Batal</x-button><x-button type="submit" :disabled="$assignments->isEmpty()">Simpan urutan</x-button></div>
        </form>
    </div>
@endsection
