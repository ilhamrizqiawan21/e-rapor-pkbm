@extends('layouts.app')

@section('content')
    @php($hasFilters = request()->filled('search') || request()->filled('status'))
    @php($activePeriodLabel = $activeAcademicYear?->name ?? 'Belum ada tahun pelajaran aktif')

    <div class="mx-auto max-w-7xl space-y-6">
        <header class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-accent-700">Data inti / sumber daya manusia</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Guru</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">Kelola akun tutor, identitas NIP/NIY, kontak, dan kesiapan penugasan untuk periode yang sedang berjalan.</p>
            </div>
            <x-button href="{{ route('admin.teachers.create') }}" icon="add">Tambah Guru</x-button>
        </header>

        @if (session('status'))
            <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" /></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <section aria-label="Ringkasan guru" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total Guru', 'value' => $summary['total'], 'detail' => 'Semua akun dalam institusi', 'tone' => 'brand'],
                ['label' => 'Guru aktif', 'value' => $summary['active'], 'detail' => 'Akun siap digunakan', 'tone' => 'success'],
                ['label' => 'Sudah ditugaskan', 'value' => $summary['assigned'], 'detail' => $summary['assigned'] === null ? 'Aktifkan tahun pelajaran dahulu' : 'Mapel atau wali kelas aktif', 'tone' => 'accent'],
                ['label' => 'Penugasan periode', 'value' => $summary['subjectAssignments'], 'detail' => $summary['subjectAssignments'] === null ? 'Menunggu periode aktif' : 'Mapel · '.$summary['homeroomClasses'].' wali kelas', 'tone' => 'warning'],
            ] as $stat)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3"><p class="text-sm font-medium text-slate-600">{{ $stat['label'] }}</p><span class="h-2.5 w-2.5 rounded-full {{ ['brand' => 'bg-brand-500', 'success' => 'bg-emerald-500', 'accent' => 'bg-accent-500', 'warning' => 'bg-amber-500'][$stat['tone']] }}"></span></div>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 tabular-nums">{{ $stat['value'] ?? '—' }}</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $stat['detail'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="teacher-directory-heading">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div><h2 id="teacher-directory-heading" class="text-lg font-semibold text-slate-950">Direktori Guru</h2><p class="mt-1 text-sm text-slate-500">Cari berdasarkan nama, username, email, atau NIP/NIY.</p></div>
                    <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $teachers->total() }} akun</span>
                </div>
            </div>
            <form method="GET" action="{{ route('admin.teachers.index') }}" class="grid gap-4 border-b border-slate-100 bg-slate-50/70 px-5 py-4 md:grid-cols-[minmax(0,1fr)_12rem_auto_auto] md:items-end sm:px-6">
                <x-input name="search" label="Cari Guru" :value="request('search')" placeholder="Nama, username, email, atau NIP/NIY" autocomplete="off" />
                <x-select name="status" label="Status" :selected="request('status')" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif']" placeholder="Semua status" />
                <x-button type="submit" variant="secondary" icon="filter">Terapkan filter</x-button>
                @if ($hasFilters)<x-button href="{{ route('admin.teachers.index') }}" variant="ghost" icon="x">Reset</x-button>@else<span class="hidden md:block"></span>@endif
            </form>
            <div class="flex items-center justify-between gap-3 px-5 py-3 text-xs text-slate-500 sm:px-6"><p>Penugasan periode: <span class="font-semibold text-slate-700">{{ $activePeriodLabel }}</span></p>@if ($hasFilters)<p class="font-medium text-accent-700">Filter sedang aktif</p>@endif</div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <caption class="sr-only">Daftar Guru dalam institusi</caption>
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><x-table.th>Guru</x-table.th><x-table.th>Identitas & jabatan</x-table.th><x-table.th>Penugasan periode aktif</x-table.th><x-table.th>Kontak</x-table.th><x-table.th>Status</x-table.th><x-table.th align="right">Aksi</x-table.th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($teachers as $teacher)
                            @php($profileCompletion = collect([$teacher->staffProfile?->employee_number, $teacher->staffProfile?->title, $teacher->staffProfile?->phone])->filter(fn ($value) => filled($value))->count())
                            <tr class="align-top transition-colors hover:bg-slate-50">
                                <x-table.td><a href="{{ route('admin.teachers.show', $teacher) }}" class="font-semibold text-brand-900 hover:text-brand-700 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">{{ $teacher->name }}</a><p class="mt-1 text-xs text-slate-500">{{ $teacher->username }}{{ $teacher->email ? ' · '.$teacher->email : '' }}</p></x-table.td>
                                <x-table.td><p class="font-medium text-slate-800">{{ $teacher->staffProfile?->employee_number ?? 'NIP / NIY belum diisi' }}</p><p class="mt-1 text-xs text-slate-500">{{ $teacher->staffProfile?->title ?? 'Jabatan belum diisi' }}</p><div class="mt-2 flex items-center gap-2"><span class="h-1.5 w-14 overflow-hidden rounded-full bg-slate-200"><span class="block h-full rounded-full bg-accent-500" style="width: {{ ($profileCompletion / 3) * 100 }}%"></span></span><span class="text-[11px] text-slate-500">{{ $profileCompletion }}/3 data</span></div></x-table.td>
                                <x-table.td>@if ($activeAcademicYear)<p class="font-medium text-slate-800">{{ $teacher->active_subject_assignments_count }} mapel aktif</p><p class="mt-1 text-xs text-slate-500">{{ $teacher->active_homeroom_classes_count }} wali kelas</p>@else<p class="text-sm text-slate-500">Menunggu periode aktif</p>@endif</x-table.td>
                                <x-table.td><p>{{ $teacher->staffProfile?->phone ?? '—' }}</p><p class="mt-1 max-w-48 truncate text-xs text-slate-500">{{ $teacher->email ?? 'Email belum diisi' }}</p></x-table.td>
                                <x-table.td><x-badge :variant="$teacher->status->value" dot>{{ $teacher->status->label() }}</x-badge></x-table.td>
                                <x-table.td align="right"><div class="flex flex-wrap justify-end gap-2"><x-button href="{{ route('admin.teachers.show', $teacher) }}" variant="secondary" size="sm" icon="eye">Detail</x-button><x-button href="{{ route('admin.teachers.edit', $teacher) }}" variant="edit" size="sm" icon="edit">Edit</x-button></div></x-table.td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-16"><div class="mx-auto max-w-md text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-700"><x-nav-icon name="teachers" /></span><h3 class="mt-4 font-semibold text-slate-900">{{ $hasFilters ? 'Tidak ada Guru yang cocok' : 'Belum ada akun Guru' }}</h3><p class="mt-2 text-sm leading-6 text-slate-500">{{ $hasFilters ? 'Coba ubah kata kunci atau reset filter untuk melihat akun lain.' : 'Tambahkan akun Guru agar penugasan mapel dan wali kelas dapat dikelola.' }}</p>@if ($hasFilters)<x-button class="mt-5" href="{{ route('admin.teachers.index') }}" variant="secondary" size="sm" icon="x">Reset filter</x-button>@else<x-button class="mt-5" href="{{ route('admin.teachers.create') }}" size="sm" icon="add">Tambah Guru</x-button>@endif</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="space-y-3 p-4 md:hidden">
                @forelse ($teachers as $teacher)
                    @php($profileCompletion = collect([$teacher->staffProfile?->employee_number, $teacher->staffProfile?->title, $teacher->staffProfile?->phone])->filter(fn ($value) => filled($value))->count())
                    <article class="rounded-2xl border border-slate-200 p-4" data-responsive-card>
                        <div class="flex items-start justify-between gap-3"><div><a href="{{ route('admin.teachers.show', $teacher) }}" class="font-semibold text-brand-900">{{ $teacher->name }}</a><p class="mt-1 text-xs text-slate-500">{{ $teacher->username }}{{ $teacher->email ? ' · '.$teacher->email : '' }}</p></div><x-badge :variant="$teacher->status->value" dot>{{ $teacher->status->label() }}</x-badge></div>
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-xs text-slate-500">NIP / NIY</dt><dd class="mt-1 font-medium text-slate-800">{{ $teacher->staffProfile?->employee_number ?? 'Belum diisi' }}</dd></div><div><dt class="text-xs text-slate-500">Jabatan</dt><dd class="mt-1 font-medium text-slate-800">{{ $teacher->staffProfile?->title ?? 'Belum diisi' }}</dd></div><div><dt class="text-xs text-slate-500">Penugasan</dt><dd class="mt-1 font-medium text-slate-800">{{ $activeAcademicYear ? $teacher->active_subject_assignments_count.' mapel · '.$teacher->active_homeroom_classes_count.' wali' : 'Periode belum aktif' }}</dd></div><div><dt class="text-xs text-slate-500">Kelengkapan</dt><dd class="mt-1 font-medium text-slate-800">{{ $profileCompletion }}/3 data pendukung</dd></div></dl>
                        <div class="mt-4 flex flex-wrap gap-2"><x-button href="{{ route('admin.teachers.show', $teacher) }}" variant="secondary" size="sm" icon="eye">Lihat detail</x-button><x-button href="{{ route('admin.teachers.edit', $teacher) }}" variant="edit" size="sm" icon="edit">Edit</x-button></div>
                    </article>
                @empty
                    <div class="px-2 py-10 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-700"><x-nav-icon name="teachers" /></span><h3 class="mt-4 font-semibold text-slate-900">{{ $hasFilters ? 'Tidak ada Guru yang cocok' : 'Belum ada akun Guru' }}</h3><p class="mt-2 text-sm leading-6 text-slate-500">{{ $hasFilters ? 'Coba ubah kata kunci atau reset filter.' : 'Tambahkan akun Guru untuk mulai mengatur penugasan.' }}</p></div>
                @endforelse
            </div>
            <div class="border-t border-slate-100 px-5 sm:px-6"><x-pagination :paginator="$teachers" /></div>
        </section>
    </div>
@endsection
