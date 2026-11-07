@extends('layouts.app')

@section('content')
    @php
        $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
        $selectedClass = $classes->firstWhere('id', (int) ($filters['class_id'] ?? 0));
        $selectedSemester = $semesters->firstWhere('id', (int) ($filters['semester_id'] ?? 0));
        $canExportBatch = $selectedClass && $selectedSemester && (auth()->user()->hasAnyRole(['ADMIN', 'KEPSEK']) || (auth()->user()->hasRole('GURU') && $selectedClass->homeroom_teacher_id === auth()->id()));
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Rapor / penerbitan" title="Cetak rapor" description="Cari peserta didik, lalu unduh identitas, nilai, atau rapor lengkap dalam format PDF.">
            @if ($canExportBatch)
                <x-button href="{{ route('admin.report-cards.pdf.batch', ['academicClass' => $selectedClass, 'semester' => $selectedSemester]) }}" variant="pdf" icon="pdf" target="_blank" rel="noopener">Cetak semua rapor kelas</x-button>
            @endif
        </x-page-header>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-label="Filter cetak rapor">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-950">Cari rapor</p>
                    <p class="mt-1 text-sm text-slate-500">Gunakan filter untuk menampilkan rapor pada periode dan kelas tertentu.</p>
                </div>
                <span class="text-xs font-medium text-slate-500">{{ $reportCards->total() }} peserta didik</span>
            </div>
            <form method="GET" action="{{ route('admin.report-cards.index') }}" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1.4fr)_14rem_14rem_14rem_auto_auto] xl:items-end">
                <x-input name="search" label="Nama peserta didik" :value="$filters['search'] ?? ''" placeholder="Contoh: Ahmad Fauzan" autocomplete="off" />
                <x-select name="academic_year_id" label="Tahun pelajaran" :selected="$filters['academic_year_id'] ?? ''" :options="$academicYears->mapWithKeys(fn ($year) => [$year->id => $year->name])->all()" placeholder="Semua tahun" />
                <x-select name="semester_id" label="Semester" :selected="$filters['semester_id'] ?? ''" :options="$semesters->mapWithKeys(fn ($semester) => [$semester->id => $semester->academicYear->name.' · '.$semester->name])->all()" placeholder="Semua semester" />
                <x-select name="class_id" label="Kelas" :selected="$filters['class_id'] ?? ''" :options="$classes->mapWithKeys(fn ($class) => [$class->id => $class->name])->all()" placeholder="Semua kelas" />
                <x-button type="submit" variant="secondary" icon="filter">Terapkan</x-button>
                @if ($hasFilters)<x-button href="{{ route('admin.report-cards.index') }}" variant="ghost" icon="x">Reset</x-button>@endif
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="report-list-title">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div><h2 id="report-list-title" class="text-lg font-semibold text-slate-950">Daftar peserta didik</h2><p class="mt-1 text-sm text-slate-500">Tiga keluaran PDF tersedia di sisi kanan setiap baris.</p></div>
                <span class="inline-flex w-fit items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800">{{ $reportCards->count() }} tampil</span>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-[1100px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50"><tr><x-table.th>Peserta didik</x-table.th><x-table.th>Kelas</x-table.th><x-table.th>Periode</x-table.th><x-table.th>Kelengkapan</x-table.th><x-table.th align="right">Ekspor PDF</x-table.th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($reportCards as $reportCard)
                            @php($summary = $progress[$reportCard->getKey()])
                            <tr class="align-top hover:bg-slate-50">
                                <x-table.td><p class="font-semibold text-slate-900">{{ $reportCard->enrollment?->student?->full_name ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $reportCard->enrollment?->student?->student_number ?? 'NIPD belum diisi' }}</p></x-table.td>
                                <x-table.td><p class="font-medium text-slate-900">{{ $reportCard->enrollment?->academicClass?->name ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $reportCard->enrollment?->academicClass?->program?->name ?? 'Program belum diisi' }}</p></x-table.td>
                                <x-table.td><p class="font-medium text-slate-900">{{ $reportCard->semester?->academicYear?->name ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $reportCard->semester?->name ?? 'Semester belum diisi' }}</p></x-table.td>
                                <x-table.td><p class="font-semibold {{ $summary['complete'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $summary['completed'] }}/{{ $summary['total'] }} komponen</p>@if ($summary['missing'] !== [])<p class="mt-1 max-w-56 text-xs text-slate-500">Belum lengkap: {{ collect($summary['missing'])->pluck('label')->implode(', ') }}</p>@else<x-badge variant="success" dot>Siap dicetak</x-badge>@endif</x-table.td>
                                <x-table.td align="right"><div class="flex flex-wrap justify-end gap-2">@can('export', $reportCard)<x-button href="{{ route('admin.report-cards.pdf.identity', $reportCard) }}" size="sm" variant="pdf" icon="pdf" target="_blank" rel="noopener">Identitas siswa</x-button><x-button href="{{ route('admin.report-cards.pdf.grades', $reportCard) }}" size="sm" variant="pdf" icon="pdf" target="_blank" rel="noopener">Nilai siswa</x-button>@if ($summary['complete'])<x-button href="{{ route('admin.report-cards.pdf.download', $reportCard) }}" size="sm" variant="pdf" icon="pdf" target="_blank" rel="noopener">Rapor siswa</x-button>@else<button type="button" disabled class="inline-flex min-h-10 cursor-not-allowed items-center justify-center gap-2 rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-400" title="Lengkapi seluruh komponen rapor terlebih dahulu"><x-icon name="pdf" />Rapor siswa</button>@endif @else<span class="text-xs text-slate-500">Bukan wilayah penerbitan Anda</span>@endcan</div></x-table.td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-16 text-center text-sm text-slate-500">Belum ada rapor yang cocok dengan filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 p-4 md:hidden">
                @forelse ($reportCards as $reportCard)
                    @php($summary = $progress[$reportCard->getKey()])
                    <article class="rounded-xl border border-slate-200 p-4" data-responsive-card>
                        <div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-slate-900">{{ $reportCard->enrollment?->student?->full_name ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $reportCard->enrollment?->student?->student_number ?? 'NIPD belum diisi' }}</p></div>@if ($summary['complete'])<x-badge variant="success" dot>Siap dicetak</x-badge>@else<x-badge variant="warning" dot>Belum lengkap</x-badge>@endif</div>
                        <p class="mt-3 text-xs text-slate-500">{{ $reportCard->enrollment?->academicClass?->name ?? 'Kelas belum diisi' }} · {{ $reportCard->semester?->academicYear?->name ?? 'Tahun belum diisi' }} · {{ $reportCard->semester?->name ?? 'Semester belum diisi' }}</p>
                        <p class="mt-3 text-xs text-slate-600">{{ $summary['completed'] }}/{{ $summary['total'] }} komponen wajib</p>
                        @can('export', $reportCard)<div class="mt-4 grid gap-2 sm:grid-cols-3"><x-button href="{{ route('admin.report-cards.pdf.identity', $reportCard) }}" size="sm" variant="pdf" icon="pdf" target="_blank" rel="noopener">Identitas PDF</x-button><x-button href="{{ route('admin.report-cards.pdf.grades', $reportCard) }}" size="sm" variant="pdf" icon="pdf" target="_blank" rel="noopener">Nilai PDF</x-button>@if ($summary['complete'])<x-button href="{{ route('admin.report-cards.pdf.download', $reportCard) }}" size="sm" variant="pdf" icon="pdf" target="_blank" rel="noopener">Rapor PDF</x-button>@else<button type="button" disabled class="inline-flex min-h-10 cursor-not-allowed items-center justify-center gap-2 rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-400"><x-icon name="pdf" />Rapor PDF</button>@endif</div>@endcan
                    </article>
                @empty
                    <p class="px-2 py-8 text-center text-sm text-slate-500">Belum ada rapor yang cocok dengan filter.</p>
                @endforelse
            </div>
            @if ($reportCards->hasPages())<div class="border-t border-slate-100 px-5 py-3"><x-pagination :paginator="$reportCards" /></div>@endif
        </section>
    </div>
@endsection
