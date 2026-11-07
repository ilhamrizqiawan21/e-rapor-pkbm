@extends('layouts.app')

@section('content')
    @php
        $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
        $selectedClass = $classes->firstWhere('id', (int) ($filters['class_id'] ?? 0));
        $selectedSemester = $semesters->firstWhere('id', (int) ($filters['semester_id'] ?? 0));
        $canExportBatch = $selectedClass && $selectedSemester && (auth()->user()->hasAnyRole(['ADMIN', 'KEPSEK']) || (auth()->user()->hasRole('GURU') && $selectedClass->homeroom_teacher_id === auth()->id()));
        $pageSummaries = $progress->values();
        $readyOnPage = $pageSummaries->where('complete', true)->count();
        $needsAttentionOnPage = $pageSummaries->where('complete', false)->count();
        $lockedOnPage = $reportCards->getCollection()->where('status', 'LOCKED')->count();
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Rapor" title="Kelengkapan & cetak rapor" description="Tinjau komponen yang belum lengkap, selesaikan pekerjaan berikutnya, lalu cetak rapor yang sudah siap.">
            @if ($canExportBatch)
                <x-button href="{{ route('admin.report-cards.pdf.batch', ['academicClass' => $selectedClass, 'semester' => $selectedSemester]) }}" variant="pdf" icon="print" target="_blank" rel="noopener">Cetak satu kelas</x-button>
            @endif
        </x-page-header>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan kelengkapan rapor">
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total hasil</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $reportCards->total() }}</p>
                <p class="mt-1 text-xs text-slate-500">Sesuai filter aktif</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50/70 px-5 py-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Perlu dilengkapi</p>
                <p class="mt-2 text-2xl font-semibold text-amber-950">{{ $needsAttentionOnPage }}</p>
                <p class="mt-1 text-xs text-amber-700">Pada halaman ini</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-5 py-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Siap dicetak</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-950">{{ $readyOnPage }}</p>
                <p class="mt-1 text-xs text-emerald-700">Pada halaman ini</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Diarsipkan</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $lockedOnPage }}</p>
                <p class="mt-1 text-xs text-slate-500">Pada halaman ini</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-label="Filter rapor">
            <form method="GET" action="{{ route('admin.report-cards.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1.4fr)_14rem_14rem_14rem_auto_auto] xl:items-end">
                <x-input name="search" label="Nama peserta didik" :value="$filters['search'] ?? ''" placeholder="Contoh: Ahmad Fauzan" autocomplete="off" />
                <x-select name="academic_year_id" label="Tahun pelajaran" :selected="$filters['academic_year_id'] ?? ''" :options="$academicYears->mapWithKeys(fn ($year) => [$year->id => $year->name])->all()" placeholder="Semua tahun" />
                <x-select name="semester_id" label="Semester" :selected="$filters['semester_id'] ?? ''" :options="$semesters->mapWithKeys(fn ($semester) => [$semester->id => $semester->academicYear->name.' · '.$semester->name])->all()" placeholder="Semua semester" />
                <x-select name="class_id" label="Kelas" :selected="$filters['class_id'] ?? ''" :options="$classes->mapWithKeys(fn ($class) => [$class->id => $class->name])->all()" placeholder="Semua kelas" />
                <x-button type="submit" variant="secondary" icon="filter">Terapkan</x-button>
                @if ($hasFilters)
                    <x-button href="{{ route('admin.report-cards.index') }}" variant="ghost" icon="x">Reset</x-button>
                @endif
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="report-list-title">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 id="report-list-title" class="text-lg font-semibold text-slate-950">Antrean kelengkapan rapor</h2>
                    <p class="mt-1 text-sm text-slate-500">Aksi utama menyesuaikan kondisi setiap rapor.</p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800">{{ $reportCards->total() }} rapor</span>
            </div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-[1040px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <x-table.th>Peserta didik</x-table.th>
                            <x-table.th>Kelas & periode</x-table.th>
                            <x-table.th>Kelengkapan</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Aksi berikutnya</x-table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($reportCards as $reportCard)
                            @php
                                $summary = $progress[$reportCard->getKey()];
                                $completionPercentage = $summary['total'] > 0 ? (int) round(($summary['completed'] / $summary['total']) * 100) : 0;
                                $missingLabels = collect($summary['missing'])->pluck('label');
                            @endphp
                            <tr class="align-middle transition-colors hover:bg-slate-50/80">
                                <x-table.td>
                                    <p class="font-semibold text-slate-950">{{ $reportCard->enrollment?->student?->full_name ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $reportCard->enrollment?->student?->student_number ?? 'NIPD belum diisi' }}</p>
                                </x-table.td>
                                <x-table.td>
                                    <p class="font-medium text-slate-900">{{ $reportCard->enrollment?->academicClass?->name ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $reportCard->semester?->academicYear?->name ?? '—' }} · {{ $reportCard->semester?->name ?? 'Semester belum diisi' }}</p>
                                </x-table.td>
                                <x-table.td>
                                    <div class="w-64 max-w-full">
                                        <div class="flex items-center justify-between gap-3 text-xs">
                                            <span class="font-semibold text-slate-700">{{ $summary['completed'] }}/{{ $summary['total'] }} komponen</span>
                                            <span class="text-slate-500">{{ $completionPercentage }}%</span>
                                        </div>
                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200" aria-hidden="true"><div class="h-full rounded-full {{ $summary['complete'] ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $completionPercentage }}%"></div></div>
                                        @if ($missingLabels->isNotEmpty())
                                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                                Kurang: {{ $missingLabels->take(2)->implode(', ') }}
                                                @if ($missingLabels->count() > 2)
                                                    <span class="font-semibold text-slate-700">+{{ $missingLabels->count() - 2 }}</span>
                                                @endif
                                            </p>
                                        @else
                                            <p class="mt-2 text-xs text-emerald-700">Seluruh komponen wajib lengkap.</p>
                                        @endif
                                    </div>
                                </x-table.td>
                                <x-table.td>
                                    @if ($reportCard->status === 'LOCKED')
                                        <x-badge variant="LOCKED" dot>Diarsipkan</x-badge>
                                    @elseif ($summary['complete'])
                                        <x-badge variant="success" dot>Siap dicetak</x-badge>
                                    @else
                                        <x-badge variant="warning" dot>Perlu tindakan</x-badge>
                                    @endif
                                </x-table.td>
                                <x-table.td align="right">
                                    <div class="flex justify-end gap-2">
                                        @if ($summary['complete'] && auth()->user()->can('export', $reportCard))
                                            <x-button href="{{ route('admin.report-cards.pdf.print', $reportCard) }}" size="sm" variant="pdf" icon="print" target="_blank" rel="noopener">Cetak rapor</x-button>
                                            <x-button href="{{ route('admin.report-cards.completeness.edit', $reportCard) }}" size="sm" variant="secondary" icon="eye">Rincian</x-button>
                                        @elseif (auth()->user()->can('manageCompleteness', $reportCard))
                                            <x-button href="{{ route('admin.report-cards.completeness.edit', $reportCard) }}" size="sm" variant="edit" icon="edit">Lengkapi ({{ count($summary['missing']) }})</x-button>
                                        @else
                                            <x-button href="{{ route('admin.report-cards.completeness.edit', $reportCard) }}" size="sm" variant="secondary" icon="eye">Lihat rincian</x-button>
                                        @endif
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-16 text-center"><p class="text-sm font-medium text-slate-700">Belum ada rapor yang cocok dengan filter.</p><p class="mt-2 text-sm text-slate-500">Rapor dibuat otomatis saat nilai pertama disimpan pada menu Input nilai.</p><x-button class="mt-4" href="{{ route('admin.grade-entry.index') }}" variant="secondary" size="sm" icon="edit">Buka input nilai</x-button></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 p-4 lg:hidden">
                @forelse ($reportCards as $reportCard)
                    @php
                        $summary = $progress[$reportCard->getKey()];
                        $completionPercentage = $summary['total'] > 0 ? (int) round(($summary['completed'] / $summary['total']) * 100) : 0;
                        $missingLabels = collect($summary['missing'])->pluck('label');
                    @endphp
                    <article class="rounded-xl border border-slate-200 bg-white p-4" data-responsive-card>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0"><p class="truncate font-semibold text-slate-950">{{ $reportCard->enrollment?->student?->full_name ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $reportCard->enrollment?->student?->student_number ?? 'NIPD belum diisi' }}</p></div>
                            @if ($reportCard->status === 'LOCKED')
                                <x-badge variant="LOCKED" dot>Arsip</x-badge>
                            @elseif ($summary['complete'])
                                <x-badge variant="success" dot>Siap</x-badge>
                            @else
                                <x-badge variant="warning" dot>Perlu tindakan</x-badge>
                            @endif
                        </div>
                        <p class="mt-3 text-xs text-slate-500">{{ $reportCard->enrollment?->academicClass?->name ?? 'Kelas belum diisi' }} · {{ $reportCard->semester?->academicYear?->name ?? 'Tahun belum diisi' }} · {{ $reportCard->semester?->name ?? 'Semester belum diisi' }}</p>
                        <div class="mt-4 rounded-lg bg-slate-50 p-3">
                            <div class="flex items-center justify-between gap-3 text-xs"><span class="font-semibold text-slate-700">{{ $summary['completed'] }}/{{ $summary['total'] }} komponen wajib</span><span class="text-slate-500">{{ $completionPercentage }}%</span></div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200" aria-hidden="true"><div class="h-full rounded-full {{ $summary['complete'] ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $completionPercentage }}%"></div></div>
                            @if ($missingLabels->isNotEmpty())
                                <p class="mt-2 text-xs leading-5 text-slate-600">
                                    Kurang: {{ $missingLabels->take(2)->implode(', ') }}
                                    @if ($missingLabels->count() > 2)
                                        +{{ $missingLabels->count() - 2 }} lainnya
                                    @endif
                                </p>
                            @endif
                        </div>
                        <div class="mt-4 grid gap-2 sm:grid-cols-2">
                            @if ($summary['complete'] && auth()->user()->can('export', $reportCard))
                                <x-button href="{{ route('admin.report-cards.pdf.print', $reportCard) }}" size="sm" variant="pdf" icon="print" target="_blank" rel="noopener">Cetak rapor</x-button>
                                <x-button href="{{ route('admin.report-cards.completeness.edit', $reportCard) }}" size="sm" variant="secondary" icon="eye">Rincian</x-button>
                            @elseif (auth()->user()->can('manageCompleteness', $reportCard))
                                <x-button href="{{ route('admin.report-cards.completeness.edit', $reportCard) }}" size="sm" variant="edit" icon="edit">Lengkapi {{ count($summary['missing']) }} komponen</x-button>
                            @else
                                <x-button href="{{ route('admin.report-cards.completeness.edit', $reportCard) }}" size="sm" variant="secondary" icon="eye">Lihat rincian</x-button>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="px-2 py-8 text-center"><p class="text-sm font-medium text-slate-700">Belum ada rapor yang cocok dengan filter.</p><p class="mt-2 text-sm text-slate-500">Mulai dari Input nilai untuk membuat rapor.</p><x-button class="mt-4" href="{{ route('admin.grade-entry.index') }}" variant="secondary" size="sm" icon="edit">Buka input nilai</x-button></div>
                @endforelse
            </div>
            @if ($reportCards->hasPages())
                <div class="border-t border-slate-100 px-5 py-3"><x-pagination :paginator="$reportCards" /></div>
            @endif
        </section>
    </div>
@endsection
