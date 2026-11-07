@php
    $metrics = collect($institutionMetrics ?? $stats ?? []);
    if ($metrics->isEmpty()) {
        $metrics = collect($stats ?? []);
    }
    $components = collect($componentSummary ?? []);
    $teachers = collect($teacherProgress ?? []);
    $recentActivities = collect($recentAudit ?? []);
    $decisions = $decisionSummary ?? [];
    $statusCounts = $statusCounts ?? [];
    $periodLabel = data_get($period ?? [], 'label', 'Belum ada periode aktif');
    $monitoringRouteAvailable = \Illuminate\Support\Facades\Route::has('admin.grade-monitoring.index');
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <header class="relative overflow-hidden rounded-3xl bg-brand-950 px-6 py-7 text-white shadow-sm sm:px-8 sm:py-8">
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full border-[28px] border-accent-400/10"></div>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-accent-300">Dashboard Kepala PKBM · Pusat insight PKBM</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Pantau kesiapan akademik dari satu layar</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Ringkasan read-only untuk membantu Kepala PKBM melihat titik tertinggal sebelum rapor diterbitkan, tanpa tahap pengajuan atau persetujuan.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Periode aktif</p>
                    <p class="mt-1 text-sm font-semibold text-white">{{ $periodLabel }}</p>
                </div>
                <x-badge variant="success" dot>Mode hanya baca</x-badge>
            </div>
        </div>
    </header>

    @if (! $hasPeriod)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900" role="status">
            <p class="font-semibold">Periode belum aktif</p>
            <p class="mt-1">Insight akan terisi setelah Admin mengaktifkan tahun pelajaran dan semester.</p>
        </section>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan institusi">
        @forelse ($metrics as $metric)
            @php
                $tone = data_get($metric, 'tone', 'brand');
                $toneClasses = match ($tone) {
                    'success' => ['border-emerald-200', 'bg-emerald-50', 'text-emerald-700'],
                    'warning' => ['border-amber-200', 'bg-amber-50', 'text-amber-800'],
                    'danger' => ['border-rose-200', 'bg-rose-50', 'text-rose-700'],
                    default => ['border-slate-200', 'bg-white', 'text-brand-900'],
                };
            @endphp
            <article class="rounded-2xl border {{ $toneClasses[0] }} {{ $toneClasses[1] }} p-5 shadow-xs">
                <p class="text-sm font-medium text-slate-500">{{ data_get($metric, 'label', 'Metrik') }}</p>
                <p class="mt-3 text-3xl font-semibold tabular-nums {{ $toneClasses[2] }}">{{ number_format((int) data_get($metric, 'value', 0)) }}{{ data_get($metric, 'suffix', '') }}</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ data_get($metric, 'detail', 'Periode aktif') }}</p>
            </article>
        @empty
            @foreach ($stats as $stat)
                <x-card>
                    <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-3xl font-semibold tabular-nums text-brand-900">{{ number_format($stat['value']) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $stat['detail'] }}</p>
                </x-card>
            @endforeach
        @endforelse
    </section>

    <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:flex-row sm:items-center sm:justify-between sm:p-6" aria-labelledby="kepsek-actions-title">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Tindak lanjut cepat</p>
            <h2 id="kepsek-actions-title" class="mt-1 text-lg font-semibold text-slate-950">Mulai dari data yang paling membutuhkan perhatian</h2>
            <p class="mt-1 text-sm text-slate-500">Semua halaman berikut aman untuk ditinjau tanpa mengubah data.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($monitoringRouteAvailable)
                <x-button href="{{ route('admin.grade-monitoring.index') }}" variant="primary" size="sm">Progres nilai</x-button>
            @endif
            <x-button href="{{ route('admin.report-cards.index') }}" variant="secondary" size="sm">Kelengkapan rapor</x-button>
            <x-button href="{{ route('admin.audit-logs.index') }}" variant="secondary" size="sm">Histori audit</x-button>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[1.35fr_0.65fr]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs" aria-labelledby="kepsek-queue-title">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Prioritas hari ini</p>
                    <h2 id="kepsek-queue-title" class="mt-1 font-semibold text-slate-950">Rapor yang perlu dipantau</h2>
                    <p class="mt-1 text-sm text-slate-500">Daftar terbaru dengan komponen wajib yang belum lengkap.</p>
                </div>
                <a href="{{ route('admin.report-cards.index') }}" class="text-sm font-semibold text-brand-800 underline decoration-brand-200 underline-offset-4 hover:text-brand-950">Lihat semua</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($reviewQueue as $reportCard)
                    <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">{{ $reportCard->enrollment?->student?->full_name ?? 'Peserta didik' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $reportCard->enrollment?->academicClass?->name ?? 'Kelas belum diisi' }} · Diperbarui {{ $reportCard->updated_at?->format('d/m/Y H:i') ?? '—' }}</p>
                        </div>
                        <x-button href="{{ route('admin.report-cards.completeness.edit', $reportCard) }}" variant="secondary" size="sm">Buka rapor</x-button>
                    </div>
                @empty
                    <p class="px-6 py-12 text-center text-sm text-slate-500">Belum ada rapor yang perlu dipantau pada periode aktif.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl bg-brand-900 p-6 text-white shadow-xs" aria-labelledby="kepsek-status-title">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-300">Publikasi rapor</p>
            <h2 id="kepsek-status-title" class="mt-1 font-semibold">Status penerbitan</h2>
            <p class="mt-1 text-xs text-slate-400">Ringkasan periode aktif, dipisahkan dari data input guru.</p>
            <dl class="mt-6 space-y-4 text-sm">
                <div class="flex items-center justify-between gap-4"><dt class="text-slate-300">Belum siap</dt><dd class="font-semibold tabular-nums">{{ number_format((int) ($statusCounts['waiting_review'] ?? 0)) }}</dd></div>
                <div class="flex items-center justify-between gap-4"><dt class="text-slate-300">Lengkap / siap cetak</dt><dd class="font-semibold tabular-nums text-accent-300">{{ number_format((int) ($statusCounts['approved'] ?? 0)) }}</dd></div>
                <div class="flex items-center justify-between gap-4"><dt class="text-slate-300">Perlu diperbarui</dt><dd class="font-semibold tabular-nums">{{ number_format((int) ($statusCounts['revisions'] ?? 0)) }}</dd></div>
                <div class="flex items-center justify-between gap-4"><dt class="text-slate-300">Terkunci</dt><dd class="font-semibold tabular-nums">{{ number_format((int) ($statusCounts['locked'] ?? 0)) }}</dd></div>
            </dl>
            <x-button href="{{ route('admin.report-cards.index') }}" variant="secondary" size="sm" class="mt-7 w-full justify-center">Buka pusat rapor</x-button>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6" aria-labelledby="component-readiness-title">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Kelengkapan komponen</p>
                <h2 id="component-readiness-title" class="mt-1 text-xl font-semibold text-slate-950">Bagian mana yang paling banyak tertinggal?</h2>
            </div>
            <p class="text-sm text-slate-500">Agregat dari rapor pada periode aktif.</p>
        </div>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($components as $component)
                @php($percent = max(0, min(100, (int) data_get($component, 'percentage', data_get($component, 'percent', data_get($component, 'completion_percent', 0))))))
                <article class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3"><div><p class="text-sm font-semibold text-slate-900">{{ data_get($component, 'label', 'Komponen') }}</p><p class="mt-1 text-xs text-slate-500">{{ data_get($component, 'detail', '') }}</p></div><span class="text-sm font-semibold tabular-nums text-brand-800">{{ $percent }}%</span></div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $percent >= 90 ? 'bg-emerald-500' : ($percent >= 60 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $percent }}%"></div></div>
                    <p class="mt-2 text-xs text-slate-500">{{ data_get($component, 'complete', data_get($component, 'completed', 0)) }} dari {{ data_get($component, 'total', 0) }} rapor lengkap</p>
                </article>
            @empty
                <p class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 sm:col-span-2 xl:col-span-3">Belum ada agregat komponen untuk periode ini.</p>
            @endforelse
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs" aria-labelledby="kepsek-class-title">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Cakupan kelas</p><h2 id="kepsek-class-title" class="mt-1 font-semibold text-slate-950">Kesiapan per kelas</h2></div>
            <p class="text-sm text-slate-500">Klik kelas dari menu Data inti untuk melihat detail read-only.</p>
        </div>
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[760px] divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50"><tr><x-table.th>Kelas</x-table.th><x-table.th>Peserta</x-table.th><x-table.th>Total rapor</x-table.th><x-table.th>Kelengkapan</x-table.th><x-table.th>Siap cetak</x-table.th><x-table.th>Terkunci</x-table.th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($classSummary as $class)
                        @php($classPercent = max(0, min(100, (int) data_get($class, 'progress_percent', data_get($class, 'completion_percent', 0)))))
                        <tr>
                            <x-table.td><p class="font-semibold text-slate-900">{{ data_get($class, 'name', 'Kelas') }}</p><p class="text-xs text-slate-500">{{ data_get($class, 'code', '—') }}</p></x-table.td>
                            <x-table.td>{{ number_format((int) data_get($class, 'student_count', 0)) }}</x-table.td>
                            <x-table.td>{{ number_format((int) data_get($class, 'report_count', 0)) }}</x-table.td>
                            <x-table.td><div class="flex min-w-32 items-center gap-2"><div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $classPercent >= 90 ? 'bg-emerald-500' : ($classPercent >= 60 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $classPercent }}%"></div></div><span class="text-xs font-semibold tabular-nums">{{ $classPercent }}%</span></div></x-table.td>
                            <x-table.td>{{ number_format((int) data_get($class, 'approved_count', 0)) }}</x-table.td>
                            <x-table.td>{{ number_format((int) data_get($class, 'locked_count', 0)) }}</x-table.td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada kelas pada periode aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="space-y-3 p-4 md:hidden">
            @forelse ($classSummary as $class)
                @php($classPercent = max(0, min(100, (int) data_get($class, 'progress_percent', data_get($class, 'completion_percent', 0)))))
                <article class="rounded-xl border border-slate-200 p-4" data-responsive-card>
                    <div class="flex items-start justify-between gap-3"><p class="font-semibold text-slate-900">{{ data_get($class, 'name', 'Kelas') }}</p><span class="text-sm font-semibold tabular-nums text-brand-800">{{ $classPercent }}%</span></div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-600" style="width: {{ $classPercent }}%"></div></div>
                    <p class="mt-2 text-xs text-slate-500">{{ data_get($class, 'student_count', 0) }} peserta · {{ data_get($class, 'report_count', 0) }} rapor · {{ data_get($class, 'approved_count', 0) }} siap cetak</p>
                </article>
            @empty
                <p class="px-2 py-8 text-center text-sm text-slate-500">Belum ada kelas pada periode aktif.</p>
            @endforelse
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6" aria-labelledby="teacher-progress-title">
            <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Akuntabilitas pengisian</p><h2 id="teacher-progress-title" class="mt-1 font-semibold text-slate-950">Progres guru</h2></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Read-only</span></div>
            <div class="mt-5 space-y-4">
                @forelse ($teachers->take(6) as $teacher)
                    @php($teacherPercent = max(0, min(100, (int) data_get($teacher, 'progress_percent', data_get($teacher, 'completion_percent', 0)))))
                    <div><div class="flex items-center justify-between gap-3 text-sm"><p class="truncate font-medium text-slate-800">{{ data_get($teacher, 'name', data_get($teacher, 'teacher_name', 'Guru')) }}</p><span class="shrink-0 text-xs font-semibold tabular-nums text-slate-500">{{ $teacherPercent }}%</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $teacherPercent >= 90 ? 'bg-emerald-500' : ($teacherPercent >= 60 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $teacherPercent }}%"></div></div><p class="mt-1 text-xs text-slate-500">{{ data_get($teacher, 'detail', data_get($teacher, 'pending', data_get($teacher, 'missing_count', 0)).' data perlu ditinjau') }}</p></div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Belum ada progres guru untuk periode ini.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6" aria-labelledby="decision-summary-title">
            <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Akhir tahun</p><h2 id="decision-summary-title" class="mt-1 font-semibold text-slate-950">Kenaikan & kelulusan</h2></div><a href="{{ route('academic-decisions.index') }}" class="text-sm font-semibold text-brand-800 underline decoration-brand-200 underline-offset-4">Buka data</a></div>
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ([['label' => 'Sudah diputuskan', 'key' => 'decided'], ['label' => 'Belum diputuskan', 'key' => 'pending'], ['label' => 'Naik kelas', 'key' => 'promote'], ['label' => 'Tetap tingkat', 'key' => 'repeat'], ['label' => 'Lulus', 'key' => 'graduate'], ['label' => 'Belum lulus', 'key' => 'not_graduated']] as $decision)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><p class="text-xs leading-5 text-slate-500">{{ $decision['label'] }}</p><p class="mt-1 text-xl font-semibold tabular-nums text-slate-900">{{ number_format((int) data_get($decisions, $decision['key'], 0)) }}</p></div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6" aria-labelledby="audit-insight-title">
        <div class="flex items-end justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Tata kelola</p><h2 id="audit-insight-title" class="mt-1 font-semibold text-slate-950">Aktivitas terbaru</h2></div><a href="{{ route('admin.audit-logs.index') }}" class="text-sm font-semibold text-brand-800 underline decoration-brand-200 underline-offset-4">Buka histori audit</a></div>
        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($recentActivities->take(6) as $activity)
                <article class="rounded-xl border border-slate-200 p-4"><p class="text-sm font-semibold text-slate-900">{{ data_get($activity, 'action_label', data_get($activity, 'action', 'Aktivitas')) }}</p><p class="mt-1 text-xs leading-5 text-slate-500">{{ data_get($activity, 'actor_name', data_get($activity, 'actor', 'Pengguna')) }} · {{ data_get($activity, 'entity_label', data_get($activity, 'entity_type', 'Data institusi')) }}</p><p class="mt-2 text-xs text-slate-400">{{ data_get($activity, 'created_at_label', data_get($activity, 'created_at', '—')) }}</p></article>
            @empty
                <p class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">Belum ada aktivitas terbaru yang perlu ditampilkan.</p>
            @endforelse
        </div>
    </section>
</div>
