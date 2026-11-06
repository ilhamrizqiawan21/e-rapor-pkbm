@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master akademik</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Penugasan mata pelajaran</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $academicClass->name }} · {{ $academicClass->program->name }} · {{ $academicClass->level->name }} · {{ $academicClass->academicYear->name }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('create', App\Models\SubjectAssignment::class)
                    <x-button href="{{ route('admin.academic-classes.subject-assignments.create', $academicClass) }}">Tambah penugasan</x-button>
                @endcan
                <x-button href="{{ route('admin.academic-classes.show', $academicClass) }}" variant="secondary">Kembali ke kelas</x-button>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        @if ($assignments->isEmpty())
            <x-empty-state title="Belum ada penugasan mapel" description="Admin dapat menambahkan mata pelajaran dan guru pengampu untuk kelas ini.">
                @can('create', App\Models\SubjectAssignment::class)
                    <x-slot:actions><x-button href="{{ route('admin.academic-classes.subject-assignments.create', $academicClass) }}">Buat penugasan pertama</x-button></x-slot:actions>
                @endcan
            </x-empty-state>
        @else
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50"><tr>
                            <x-table.th>No.</x-table.th>
                            <x-table.th>Mata pelajaran</x-table.th>
                            <x-table.th>Guru pengampu</x-table.th>
                            <x-table.th align="center">KKM</x-table.th>
                            <x-table.th align="center">Status</x-table.th>
                            <x-table.th align="right">Aksi</x-table.th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($assignments as $assignment)
                                <tr class="hover:bg-slate-50">
                                    <x-table.td>{{ $assignment->display_order }}</x-table.td>
                                    <x-table.td><p class="font-semibold text-slate-900">{{ $assignment->subject->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $assignment->subject->code }}{{ $assignment->subject->active ? '' : ' · Mapel nonaktif' }}</p></x-table.td>
                                    <x-table.td><p>{{ $assignment->teacher->name }}</p>@if ($assignment->teacher->staffProfile?->employee_number)<p class="mt-1 text-xs text-slate-500">{{ $assignment->teacher->staffProfile->employee_number }}</p>@endif</x-table.td>
                                    <x-table.td align="center">{{ $assignment->minimum_score }}</x-table.td>
                                    <x-table.td><x-badge :variant="$assignment->active ? 'ACTIVE' : 'INACTIVE'" dot>{{ $assignment->active ? 'Aktif' : 'Nonaktif' }}</x-badge></x-table.td>
                                    <x-table.td align="right">
                                        @can('update', $assignment)
                                            <x-button size="sm" variant="secondary" href="{{ route('admin.academic-classes.subject-assignments.edit', [$academicClass, $assignment]) }}">Edit</x-button>
                                        @endcan
                                        @can('delete', $assignment)
                                            <form class="mt-2 sm:mt-0 sm:ml-1 inline-block" method="POST" action="{{ route('admin.academic-classes.subject-assignments.destroy', [$academicClass, $assignment]) }}" onsubmit="return confirm('Hapus penugasan mata pelajaran ini?');">
                                                @csrf @method('DELETE')
                                                <x-button type="submit" size="sm" variant="danger">Hapus</x-button>
                                            </form>
                                        @endcan
                                    </x-table.td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
