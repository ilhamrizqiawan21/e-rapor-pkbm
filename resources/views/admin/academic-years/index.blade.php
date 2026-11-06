@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master data</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Tahun Pelajaran</h2>
                <p class="mt-2 text-sm text-slate-600">Kelola periode akademik dan semester PKBM.</p>
            </div>
            @can('create', App\Models\AcademicYear::class)
                <x-button href="{{ route('admin.academic-years.create') }}">Tambah tahun pelajaran</x-button>
            @endcan
        </div>

        @if ($academicYears->isEmpty())
            <x-empty-state title="Belum ada tahun pelajaran" description="Buat tahun pelajaran pertama untuk mulai mengelola semester dan kelas." />
        @else
            <div class="space-y-4">
                @foreach ($academicYears as $academicYear)
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <h3 class="text-xl font-semibold text-slate-950">{{ $academicYear->name }}</h3>
                                    <x-badge variant="{{ strtolower($academicYear->status->value) }}" dot>{{ $academicYear->status->label() }}</x-badge>
                                </div>
                                <p class="mt-2 text-sm text-slate-500">{{ $academicYear->start_date?->translatedFormat('d F Y') }} — {{ $academicYear->end_date?->translatedFormat('d F Y') }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-button size="sm" variant="secondary" href="{{ route('admin.academic-years.show', $academicYear) }}">Detail semester</x-button>
                                @can('update', $academicYear)
                                    <x-button size="sm" href="{{ route('admin.academic-years.edit', $academicYear) }}">Edit</x-button>
                                @endcan
                            </div>
                        </div>
                        <div class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Semester</p><p class="mt-1 font-semibold text-slate-900">{{ $academicYear->semesters_count }} semester</p></div>
                            <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kelas</p><p class="mt-1 font-semibold text-slate-900">{{ $academicYear->classes_count }} kelas</p></div>
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
@endsection
