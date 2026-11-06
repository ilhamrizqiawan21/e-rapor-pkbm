@extends('layouts.app')
@section('content')
    @php($statusLabels = ['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'GRADUATED' => 'Lulus', 'TRANSFERRED' => 'Pindah'])
    @php($hasFilters = request()->filled('search') || request()->filled('status'))
    @php($activePeriodLabel = $activeAcademicYear?->name ?? 'Belum ada tahun pelajaran aktif')
    <div class="mx-auto max-w-7xl space-y-6">
        <header class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-accent-700">Data inti / peserta didik</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Peserta didik</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">Kelola identitas, pantau penempatan periode aktif, dan pastikan data siap dipakai untuk rapor.</p>
            </div>
            @can('create', App\Models\Student::class)
                <div class="flex flex-wrap gap-2">
                    <x-button href="{{ route('admin.students.import.create') }}" variant="secondary" icon="upload">Import data</x-button>
                    <x-button href="{{ route('admin.students.create') }}" icon="add">Tambah peserta didik</x-button>
                </div>
            @endcan
        </header>

        @if (session('status'))
            <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" /></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <section aria-label="Ringkasan peserta didik" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total peserta didik', 'value' => $summary['total'], 'detail' => 'Semua status dalam institusi', 'tone' => 'brand'],
                ['label' => 'Peserta didik aktif', 'value' => $summary['active'], 'detail' => 'Masih terdaftar sebagai aktif', 'tone' => 'success'],
                ['label' => 'Belum ditempatkan', 'value' => $summary['unplaced'], 'detail' => $summary['unplaced'] === null ? 'Aktifkan tahun pelajaran dahulu' : 'Pada '.$activePeriodLabel, 'tone' => 'warning'],
                ['label' => 'Status lain', 'value' => $summary['otherStatuses'], 'detail' => 'Nonaktif, lulus, atau pindah', 'tone' => 'neutral'],
            ] as $stat)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium text-slate-600">{{ $stat['label'] }}</p>
                        <span class="h-2.5 w-2.5 rounded-full {{ ['brand' => 'bg-brand-500', 'success' => 'bg-emerald-500', 'warning' => 'bg-amber-500', 'neutral' => 'bg-slate-400'][$stat['tone']] }}"></span>
                    </div>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 tabular-nums">{{ $stat['value'] ?? '—' }}</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $stat['detail'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="student-directory-heading">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 id="student-directory-heading" class="text-lg font-semibold text-slate-950">Direktori peserta didik</h2>
                        <p class="mt-1 text-sm text-slate-500">Cari berdasarkan nama, NIPD, atau NISN. Klik nama untuk melihat histori lengkap.</p>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $students->total() }} data</span>
                </div>
            </div>
            <form method="GET" action="{{ route('admin.students.index') }}" class="grid gap-4 border-b border-slate-100 bg-slate-50/70 px-5 py-4 md:grid-cols-[minmax(0,1fr)_12rem_auto_auto] md:items-end sm:px-6">
                <x-input name="search" label="Cari peserta didik" :value="request('search')" placeholder="Nama, NIPD, atau NISN" autocomplete="off" />
                <x-select name="status" label="Status" :selected="request('status')" :options="$statusLabels" placeholder="Semua status" />
                <x-button type="submit" variant="secondary" icon="filter">Terapkan filter</x-button>
                @if ($hasFilters)
                    <x-button href="{{ route('admin.students.index') }}" variant="ghost" icon="x">Reset</x-button>
                @else
                    <span class="hidden md:block"></span>
                @endif
            </form>
            <div class="flex items-center justify-between gap-3 px-5 py-3 text-xs text-slate-500 sm:px-6">
                <p>Periode penempatan: <span class="font-semibold text-slate-700">{{ $activePeriodLabel }}</span></p>
                @if ($hasFilters)<p class="font-medium text-accent-700">Filter sedang aktif</p>@endif
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <caption class="sr-only">Daftar peserta didik</caption>
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr><x-table.th>Identitas</x-table.th><x-table.th>Peserta didik</x-table.th><x-table.th>Penempatan periode aktif</x-table.th><x-table.th>Kelengkapan</x-table.th><x-table.th>Status</x-table.th><x-table.th align="right">Aksi</x-table.th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($students as $student)
                            @php($activeEnrollment = $student->relationLoaded('enrollments') ? $student->enrollments->first() : null)
                            @php($profileFields = [$student->national_student_number, $student->birth_date, $student->guardian_name])
                            @php($profileCompletion = collect($profileFields)->filter(fn ($value) => filled($value))->count())
                            <tr class="align-top transition-colors hover:bg-slate-50">
                                <x-table.td><p class="font-semibold text-slate-900">{{ $student->student_number }}</p><p class="mt-1 text-xs text-slate-500">NISN: {{ $student->national_student_number ?? '—' }}</p></x-table.td>
                                <x-table.td><a href="{{ route('admin.students.show', $student) }}" class="font-semibold text-brand-900 hover:text-brand-700 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">{{ $student->full_name }}</a><p class="mt-1 text-xs text-slate-500">{{ $student->gender === 'L' ? 'Laki-laki' : ($student->gender === 'P' ? 'Perempuan' : 'Jenis kelamin belum diisi') }}</p></x-table.td>
                                <x-table.td>@if ($activeEnrollment?->academicClass)<p class="font-medium text-slate-800">{{ $activeEnrollment->academicClass->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $activeEnrollment->academicClass->program->name }} · {{ $activeEnrollment->academicClass->level->name }}</p>@elseif ($activeAcademicYear)<x-badge variant="warning" dot>Belum ditempatkan</x-badge><p class="mt-1 text-xs text-slate-500">Atur melalui Kelas / rombel</p>@else<p class="text-sm text-slate-500">Menunggu periode aktif</p>@endif</x-table.td>
                                <x-table.td><div class="flex items-center gap-2"><span class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-200"><span class="block h-full rounded-full bg-accent-500" style="width: {{ ($profileCompletion / 3) * 100 }}%"></span></span><span class="text-xs font-medium text-slate-600">{{ $profileCompletion }}/3</span></div><p class="mt-1 text-xs text-slate-500">Data pendukung</p></x-table.td>
                                <x-table.td><x-badge :variant="$student->status" dot>{{ $statusLabels[$student->status] ?? $student->status }}</x-badge><p class="mt-2 text-xs text-slate-500">{{ $student->enrollments_count }} histori penempatan</p></x-table.td>
                                <x-table.td align="right"><div class="flex flex-wrap justify-end gap-2"><x-button href="{{ route('admin.students.show', $student) }}" variant="secondary" size="sm" icon="eye">Detail</x-button>@can('update', $student)<x-button href="{{ route('admin.students.edit', $student) }}" variant="edit" size="sm" icon="edit">Edit</x-button>@endcan</div></x-table.td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-16"><div class="mx-auto max-w-md text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-700"><x-nav-icon name="students" /></span><h3 class="mt-4 font-semibold text-slate-900">{{ $hasFilters ? 'Tidak ada data yang cocok' : 'Belum ada peserta didik' }}</h3><p class="mt-2 text-sm leading-6 text-slate-500">{{ $hasFilters ? 'Coba ubah kata kunci atau reset filter untuk melihat data lain.' : 'Tambahkan satu peserta didik secara manual atau gunakan import template untuk memulai.' }}</p>@if ($hasFilters)<x-button class="mt-5" href="{{ route('admin.students.index') }}" variant="secondary" size="sm" icon="x">Reset filter</x-button>@endif @can('create', App\Models\Student::class)<x-button class="mt-5" href="{{ route('admin.students.create') }}" size="sm" icon="add">Tambah manual</x-button>@endcan</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="space-y-3 p-4 md:hidden">
                @forelse ($students as $student)
                    @php($activeEnrollment = $student->relationLoaded('enrollments') ? $student->enrollments->first() : null)
                    @php($profileCompletion = collect([$student->national_student_number, $student->birth_date, $student->guardian_name])->filter(fn ($value) => filled($value))->count())
                    <article class="rounded-2xl border border-slate-200 p-4" data-responsive-card>
                        <div class="flex items-start justify-between gap-3"><div><a href="{{ route('admin.students.show', $student) }}" class="font-semibold text-brand-900">{{ $student->full_name }}</a><p class="mt-1 text-xs text-slate-500">NIPD {{ $student->student_number }} · NISN {{ $student->national_student_number ?? '—' }}</p></div><x-badge :variant="$student->status" dot>{{ $statusLabels[$student->status] ?? $student->status }}</x-badge></div>
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-xs text-slate-500">Penempatan</dt><dd class="mt-1 font-medium text-slate-800">{{ $activeEnrollment?->academicClass?->name ?? ($activeAcademicYear ? 'Belum ditempatkan' : 'Periode belum aktif') }}</dd></div><div><dt class="text-xs text-slate-500">Kelengkapan</dt><dd class="mt-1 font-medium text-slate-800">{{ $profileCompletion }}/3 data pendukung</dd></div><div><dt class="text-xs text-slate-500">Tempat, tanggal lahir</dt><dd class="mt-1 font-medium text-slate-800">{{ $student->birth_place ?? '—' }}, {{ $student->birth_date?->format('d/m/Y') ?? '—' }}</dd></div><div><dt class="text-xs text-slate-500">Orang tua / wali</dt><dd class="mt-1 font-medium text-slate-800">{{ $student->guardian_name ?? '—' }}</dd></div></dl>
                        <p class="mt-3 text-xs text-slate-500">{{ $student->enrollments_count }} histori penempatan</p><div class="mt-4 flex flex-wrap gap-2"><x-button href="{{ route('admin.students.show', $student) }}" variant="secondary" size="sm" icon="eye">Lihat detail</x-button>@can('update', $student)<x-button href="{{ route('admin.students.edit', $student) }}" variant="edit" size="sm" icon="edit">Edit</x-button>@endcan</div>
                    </article>
                @empty
                    <div class="px-2 py-10 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-700"><x-nav-icon name="students" /></span><h3 class="mt-4 font-semibold text-slate-900">{{ $hasFilters ? 'Tidak ada data yang cocok' : 'Belum ada peserta didik' }}</h3><p class="mt-2 text-sm leading-6 text-slate-500">{{ $hasFilters ? 'Coba ubah kata kunci atau reset filter.' : 'Tambahkan data peserta didik untuk mulai mengelola penempatan.' }}</p></div>
                @endforelse
            </div>
            <div class="border-t border-slate-100 px-5 sm:px-6"><x-pagination :paginator="$students" /></div>
        </section>
    </div>
@endsection
