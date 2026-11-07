<div class="mx-auto max-w-7xl space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Dashboard Kepala PKBM</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Pantau rapor peserta didik</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Lihat kesiapan data dan hasil rapor tanpa tahap pengajuan atau persetujuan.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 sm:text-right"><p class="text-xs text-slate-500">Periode aktif</p><p class="mt-1 text-sm font-semibold text-slate-900">{{ $period['label'] }}</p></div>
    </header>

    @if (! $hasPeriod)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900" role="status"><p class="font-semibold">Periode belum aktif</p><p class="mt-1">Dashboard akan menampilkan kesiapan rapor setelah Admin mengaktifkan tahun pelajaran dan semester.</p></section>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan rapor">
        @foreach ($stats as $stat)
            <x-card><p class="text-sm text-slate-500">{{ $stat['label'] }}</p><p class="mt-3 text-2xl font-semibold tabular-nums text-brand-900">{{ number_format($stat['value']) }}</p><p class="mt-1 text-xs text-slate-500">{{ $stat['detail'] }}</p></x-card>
        @endforeach
    </section>

    <div class="grid gap-6 lg:grid-cols-[1.35fr_0.65fr]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs" aria-labelledby="kepsek-queue-title">
            <div class="border-b border-slate-100 px-6 py-5"><h3 id="kepsek-queue-title" class="font-semibold text-slate-950">Rapor yang perlu dipantau</h3><p class="mt-1 text-sm text-slate-500">Buka detail kelengkapan atau langsung lihat dokumen yang tersedia.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse ($reviewQueue as $reportCard)
                    <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="font-semibold text-slate-900">{{ $reportCard->enrollment?->student?->full_name ?? 'Peserta didik' }}</p><p class="mt-1 text-xs text-slate-500">{{ $reportCard->enrollment?->academicClass?->name ?? 'Kelas belum diisi' }} · Diperbarui {{ $reportCard->updated_at?->format('d/m/Y H:i') ?? '—' }}</p></div>
                        <x-button href="{{ route('admin.report-cards.completeness.edit', $reportCard) }}" variant="secondary" size="sm">Buka rapor</x-button>
                    </div>
                @empty
                    <p class="px-6 py-12 text-center text-sm text-slate-500">Belum ada rapor yang perlu dipantau pada periode aktif.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl bg-brand-900 p-6 text-white shadow-xs" aria-labelledby="kepsek-status-title">
            <p id="kepsek-status-title" class="text-sm font-semibold text-accent-300">Status penerbitan</p>
            <p class="mt-1 text-xs text-slate-400">Ringkasan rapor pada periode aktif.</p>
            <div class="mt-5 space-y-4 text-sm"><div class="flex items-center justify-between"><span class="text-slate-300">Belum siap dicetak</span><strong class="tabular-nums">{{ $statusCounts['draft'] + $statusCounts['submitted'] + $statusCounts['in_review'] + $statusCounts['revisions'] }}</strong></div><div class="flex items-center justify-between"><span class="text-slate-300">Siap dicetak</span><strong class="tabular-nums">{{ $statusCounts['approved'] }}</strong></div><div class="flex items-center justify-between"><span class="text-slate-300">Terkunci</span><strong class="tabular-nums">{{ $statusCounts['locked'] }}</strong></div></div>
            <x-button href="{{ route('admin.report-cards.index') }}" variant="secondary" size="sm" class="mt-7 w-full justify-center">Buka cetak rapor</x-button>
        </section>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs" aria-labelledby="kepsek-class-title">
        <div class="border-b border-slate-100 px-6 py-5"><h3 id="kepsek-class-title" class="font-semibold text-slate-950">Kesiapan per kelas</h3><p class="mt-1 text-sm text-slate-500">Gunakan ringkasan ini untuk melihat kelas yang masih perlu dilengkapi.</p></div>
        <div class="hidden overflow-x-auto md:block"><table class="min-w-[680px] divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><x-table.th>Kelas</x-table.th><x-table.th>Total rapor</x-table.th><x-table.th>Belum siap</x-table.th><x-table.th>Siap cetak</x-table.th><x-table.th>Terkunci</x-table.th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($classSummary as $class)<tr><x-table.td><p class="font-semibold text-slate-900">{{ $class->name }}</p><p class="text-xs text-slate-500">{{ $class->code }}</p></x-table.td><x-table.td>{{ $class->report_count }}</x-table.td><x-table.td>{{ $class->waiting_review_count + $class->revision_count }}</x-table.td><x-table.td>{{ $class->approved_count }}</x-table.td><x-table.td>{{ $class->locked_count }}</x-table.td></tr>@empty<tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada kelas pada periode aktif.</td></tr>@endforelse</tbody></table></div>
        <div class="space-y-3 p-4 md:hidden">@forelse ($classSummary as $class)<article class="rounded-xl border border-slate-200 p-4" data-responsive-card><p class="font-semibold text-slate-900">{{ $class->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $class->report_count }} rapor · {{ $class->approved_count }} siap cetak · {{ $class->locked_count }} terkunci</p></article>@empty<p class="px-2 py-8 text-center text-sm text-slate-500">Belum ada kelas pada periode aktif.</p>@endforelse</div>
    </section>
</div>
