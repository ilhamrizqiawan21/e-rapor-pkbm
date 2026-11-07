@extends('layouts.app')

@php
    $selectedYearId = $filters['academic_year_id'] ?? $selectedSemester?->academic_year_id;
    $selectedClassId = $filters['class_id'] ?? null;
    $selectedSubjectId = $filters['subject_id'] ?? null;
    $selectedTeacherId = $filters['teacher_id'] ?? null;
@endphp

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Insight PKBM / monitoring akademik" title="Progres nilai" description="Pantau kelengkapan nilai dan deskripsi per kelas, mata pelajaran, serta Guru tanpa mengubah data penilaian.">
            <x-badge variant="info" dot>Mode hanya baca</x-badge>
        </x-page-header>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="grade-monitoring-filter-title">
            <div class="mb-5">
                <h2 id="grade-monitoring-filter-title" class="text-base font-semibold text-slate-950">Ruang lingkup monitoring</h2>
                <p class="mt-1 text-sm text-slate-500">Gunakan filter untuk melihat progres pada periode dan penugasan tertentu.</p>
            </div>
            <form method="GET" action="{{ route('admin.grade-monitoring.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5 xl:items-end">
                <x-select name="academic_year_id" label="Tahun pelajaran" :selected="$selectedYearId">
                    <option value="">Semua tahun</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}" @selected((string) $selectedYearId === (string) $year->id)>{{ $year->name }}</option>
                    @endforeach
                </x-select>
                <x-select name="semester_id" label="Semester" :selected="$filters['semester_id'] ?? $selectedSemester?->id">
                    <option value="">Periode aktif / terbaru</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected((string) ($filters['semester_id'] ?? $selectedSemester?->id) === (string) $semester->id)>{{ $semester->academicYear?->name }} · {{ $semester->name }}</option>
                    @endforeach
                </x-select>
                <x-select name="class_id" label="Kelas" :selected="$selectedClassId">
                    <option value="">Semua kelas</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected((string) $selectedClassId === (string) $class->id)>{{ $class->name }}</option>
                    @endforeach
                </x-select>
                <x-select name="subject_id" label="Mata pelajaran" :selected="$selectedSubjectId">
                    <option value="">Semua mapel</option>
                    @foreach ($subjectOptions as $subject)
                        <option value="{{ $subject->id }}" @selected((string) $selectedSubjectId === (string) $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </x-select>
                <x-select name="teacher_id" label="Guru" :selected="$selectedTeacherId">
                    <option value="">Semua Guru</option>
                    @foreach ($teacherOptions as $teacher)
                        <option value="{{ $teacher->id }}" @selected((string) $selectedTeacherId === (string) $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </x-select>
                <div class="flex flex-wrap gap-2 md:col-span-2 xl:col-span-5">
                    <x-button type="submit" icon="filter">Terapkan filter</x-button>
                    <x-button href="{{ route('admin.grade-monitoring.index') }}" variant="ghost" icon="x">Reset</x-button>
                </div>
            </form>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Ringkasan progres nilai">
            <x-card><p class="text-sm text-slate-500">Penugasan aktif</p><p class="mt-3 text-2xl font-semibold tabular-nums text-brand-900">{{ number_format($summary['assignments']) }}</p><p class="mt-1 text-xs text-slate-500">Kelas dan mapel terpilih</p></x-card>
            <x-card><p class="text-sm text-slate-500">Penugasan lengkap</p><p class="mt-3 text-2xl font-semibold tabular-nums text-emerald-700">{{ number_format($summary['complete_assignments']) }}</p><p class="mt-1 text-xs text-slate-500">Nilai dan deskripsi 100%</p></x-card>
            <x-card><p class="text-sm text-slate-500">Target nilai</p><p class="mt-3 text-2xl font-semibold tabular-nums text-slate-900">{{ number_format($summary['expected_grades']) }}</p><p class="mt-1 text-xs text-slate-500">Siswa × penugasan</p></x-card>
            <x-card><p class="text-sm text-slate-500">Nilai lengkap</p><p class="mt-3 text-2xl font-semibold tabular-nums text-sky-700">{{ number_format($summary['filled_grades']) }}</p><p class="mt-1 text-xs text-slate-500">{{ $summary['progress_percent'] }}% dari target</p></x-card>
            <x-card><p class="text-sm text-slate-500">Masih kosong</p><p class="mt-3 text-2xl font-semibold tabular-nums text-amber-700">{{ number_format($summary['missing_grades']) }}</p><p class="mt-1 text-xs text-slate-500">Perlu ditindaklanjuti</p></x-card>
        </section>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.6fr)]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="assignment-monitoring-title">
                <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                    <div>
                        <h2 id="assignment-monitoring-title" class="text-lg font-semibold text-slate-950">Kesiapan per penugasan</h2>
                        <p class="mt-1 text-sm text-slate-500">Baris merah atau amber menunjukkan pekerjaan yang perlu ditindaklanjuti.</p>
                    </div>
                    @if ($selectedSemester)
                        <x-badge variant="brand" dot>{{ $selectedSemester->academicYear?->name }} · {{ $selectedSemester->name }}</x-badge>
                    @endif
                </div>
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[760px] divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50"><tr><x-table.th>Kelas / mapel</x-table.th><x-table.th>Guru</x-table.th><x-table.th>Progres</x-table.th><x-table.th align="right">Kekurangan</x-table.th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $assignment)
                                <tr class="align-top hover:bg-slate-50">
                                    <x-table.td><p class="font-semibold text-slate-900">{{ $assignment->subject?->name ?? 'Mapel belum tersedia' }}</p><p class="mt-1 text-xs text-slate-500">{{ $assignment->academicClass?->name ?? 'Kelas belum tersedia' }} · KKM {{ $assignment->minimum_score }}</p></x-table.td>
                                    <x-table.td>{{ $assignment->teacher?->name ?? 'Belum ditentukan' }}</x-table.td>
                                    <x-table.td><div class="min-w-44"><div class="flex items-center justify-between gap-3 text-xs"><span class="font-semibold text-slate-700">{{ $assignment->filled_count }}/{{ $assignment->expected_count }}</span><span class="font-semibold {{ $assignment->progress_percent === 100 ? 'text-emerald-700' : 'text-amber-700' }}">{{ $assignment->progress_percent }}%</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full {{ $assignment->progress_percent === 100 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $assignment->progress_percent }}%"></div></div></div></x-table.td>
                                    <x-table.td align="right"><span class="font-semibold {{ $assignment->missing_count === 0 ? 'text-emerald-700' : 'text-amber-700' }}">{{ $assignment->missing_count }}</span></x-table.td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada penugasan pada filter yang dipilih.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="space-y-3 p-4 md:hidden">
                    @forelse ($rows as $assignment)
                        <article class="rounded-xl border border-slate-200 p-4" data-responsive-card>
                            <div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-slate-900">{{ $assignment->subject?->name ?? 'Mapel belum tersedia' }}</p><p class="mt-1 text-xs text-slate-500">{{ $assignment->academicClass?->name ?? 'Kelas belum tersedia' }}</p></div><span class="text-sm font-semibold {{ $assignment->progress_percent === 100 ? 'text-emerald-700' : 'text-amber-700' }}">{{ $assignment->progress_percent }}%</span></div>
                            <p class="mt-3 text-sm text-slate-600">{{ $assignment->teacher?->name ?? 'Guru belum ditentukan' }}</p>
                            <div class="mt-3 flex items-center justify-between text-xs text-slate-500"><span>{{ $assignment->filled_count }}/{{ $assignment->expected_count }} nilai lengkap</span><span>{{ $assignment->missing_count }} kosong</span></div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full {{ $assignment->progress_percent === 100 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $assignment->progress_percent }}%"></div></div>
                        </article>
                    @empty
                        <p class="px-2 py-8 text-center text-sm text-slate-500">Belum ada penugasan pada filter yang dipilih.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl bg-brand-950 p-6 text-white shadow-sm" aria-labelledby="teacher-readiness-title">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-300">Prioritas tindak lanjut</p>
                <h2 id="teacher-readiness-title" class="mt-2 text-lg font-semibold">Progres per Guru</h2>
                <p class="mt-1 text-sm leading-6 text-slate-300">Urutan dimulai dari progres terendah agar Kepala PKBM dapat langsung melihat titik yang membutuhkan perhatian.</p>
                <div class="mt-5 space-y-4">
                    @forelse ($teacherSummary as $teacher)
                        <div><div class="flex items-center justify-between gap-3 text-sm"><span class="font-medium text-white">{{ $teacher['name'] }}</span><span class="tabular-nums text-accent-200">{{ $teacher['progress_percent'] }}%</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full {{ $teacher['progress_percent'] === 100 ? 'bg-emerald-400' : 'bg-accent-400' }}" style="width: {{ $teacher['progress_percent'] }}%"></div></div><p class="mt-1 text-xs text-slate-400">{{ $teacher['missing'] }} nilai belum lengkap · {{ $teacher['assignments'] }} penugasan</p></div>
                    @empty
                        <p class="rounded-xl border border-white/10 bg-white/5 px-4 py-6 text-center text-sm text-slate-300">Belum ada data Guru pada filter ini.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
