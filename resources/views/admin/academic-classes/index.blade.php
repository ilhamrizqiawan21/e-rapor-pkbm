@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master akademik</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Kelas / Rombel</h2>
                <p class="mt-2 text-sm text-slate-600">Daftar kelas yang tersedia sesuai akses akun dan penugasan Anda.</p>
            </div>
            @can('create', App\Models\AcademicClass::class)
                <x-button href="{{ route('admin.academic-classes.create') }}">Tambah kelas</x-button>
            @endcan
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        @if ($classes->isEmpty())
            <x-empty-state title="Belum ada kelas" description="Kelas yang dapat Anda akses akan tampil di halaman ini.">
                @can('create', App\Models\AcademicClass::class)
                    <x-slot:actions><x-button href="{{ route('admin.academic-classes.create') }}">Buat kelas pertama</x-button></x-slot:actions>
                @endcan
            </x-empty-state>
        @else
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50"><tr>
                            <x-table.th>Kelas</x-table.th>
                            <x-table.th>Periode / Program</x-table.th>
                            <x-table.th>Wali kelas</x-table.th>
                            <x-table.th align="center">Siswa</x-table.th>
                            <x-table.th align="center">Mapel</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Aksi</x-table.th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($classes as $academicClass)
                                <tr class="hover:bg-slate-50">
                                    <x-table.td>
                                        <a class="font-semibold text-brand-900 hover:text-brand-700" href="{{ route('admin.academic-classes.show', $academicClass) }}">{{ $academicClass->name }}</a>
                                        <p class="mt-1 text-xs text-slate-500">{{ $academicClass->code }} · Tingkat {{ $academicClass->level->number }}</p>
                                    </x-table.td>
                                    <x-table.td><p>{{ $academicClass->academicYear->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $academicClass->program->name }}</p></x-table.td>
                                    <x-table.td>{{ $academicClass->homeroomTeacher?->name ?? 'Belum ditetapkan' }}</x-table.td>
                                    <x-table.td align="center">{{ $academicClass->active_enrollments_count }}</x-table.td>
                                    <x-table.td align="center">{{ $academicClass->active_subject_assignments_count }}</x-table.td>
                                    <x-table.td><x-badge :variant="$academicClass->status" dot>{{ $academicClass->status === 'ACTIVE' ? 'Aktif' : 'Nonaktif' }}</x-badge></x-table.td>
                                    <x-table.td align="right"><x-button size="sm" variant="secondary" href="{{ route('admin.academic-classes.show', $academicClass) }}">Lihat detail</x-button></x-table.td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 sm:px-6"><x-pagination :paginator="$classes" /></div>
            </div>
        @endif
    </div>
@endsection
