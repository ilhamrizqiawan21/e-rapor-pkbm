@php
    // The Admin dashboard is intentionally setup-first: the next operational step must be obvious.
    $nextStep = collect($setupSteps)->first(fn (array $step): bool => ! $step['done']);
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <header class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Dashboard Admin</p>
            <h2 class="mt-2 text-balance text-3xl font-semibold tracking-tight text-slate-950">Operasional PKBM dalam satu layar</h2>
            <p class="mt-2 max-w-2xl text-pretty text-sm leading-6 text-slate-600">Mulai dari fondasi periode, pantau kesiapan data, lalu pastikan nilai dan rapor bergerak sampai siap disahkan.</p>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-xs lg:min-w-64 lg:justify-between">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-nav-icon name="academic-year" /></span>
            <div class="min-w-0 lg:text-right">
                <p class="text-xs text-slate-500">Periode aktif</p>
                <p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ $period['label'] }}</p>
            </div>
        </div>
    </header>

    @if ($nextStep)
        <section class="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-950 shadow-xs sm:flex-row sm:items-center sm:justify-between" role="status" aria-labelledby="admin-next-step-title">
            <div class="flex gap-3">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700"><x-nav-icon name="dashboard" /></span>
                <div>
                    <p id="admin-next-step-title" class="font-semibold">Langkah berikutnya: {{ $nextStep['label'] }}</p>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-amber-900">{{ $nextStep['detail'] }} Setelah langkah ini selesai, lanjutkan ke tahap berikutnya agar proses rapor dapat berjalan.</p>
                </div>
            </div>
            <x-button href="{{ route($nextStep['route']) }}" variant="warning" size="sm" class="shrink-0">{{ $nextStep['action'] }}</x-button>
        </section>
    @else
        <section class="flex flex-col gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-950 shadow-xs sm:flex-row sm:items-center sm:justify-between" role="status">
            <div class="flex gap-3"><span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"><x-nav-icon name="report" /></span><div><p class="font-semibold">Fondasi periode sudah lengkap</p><p class="mt-1 text-sm leading-6 text-emerald-900">Data siap dipantau. Gunakan ringkasan di bawah untuk menemukan kelas atau rapor yang perlu ditindaklanjuti.</p></div></div>
            <x-button href="{{ route('admin.grade-entry.index') }}" variant="success" size="sm" class="shrink-0">Buka input nilai</x-button>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6" aria-labelledby="setup-flow-title">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-accent-700">Urutan kerja Admin</p><h3 id="setup-flow-title" class="mt-1 text-lg font-semibold text-slate-950">Siapkan periode sampai siap menilai</h3><p class="mt-1 text-sm leading-6 text-slate-500">Empat fondasi ini membuat angka dashboard dan status rapor dapat dibaca dengan benar.</p></div>
            <span class="inline-flex w-fit items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold tabular-nums text-brand-800">{{ $setupCompleted }}/{{ count($setupSteps) }} selesai</span>
        </div>
        <ol class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($setupSteps as $index => $step)
                @php($isNext = $nextStep && $nextStep['key'] === $step['key'])
                <li class="relative rounded-xl border p-4 {{ $step['done'] ? 'border-emerald-200 bg-emerald-50/70' : ($isNext ? 'border-accent-300 bg-accent-50/60 ring-1 ring-accent-200' : 'border-slate-200 bg-slate-50/70') }}">
                    <div class="flex items-center justify-between gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg text-sm font-bold {{ $step['done'] ? 'bg-emerald-600 text-white' : ($isNext ? 'bg-accent-600 text-white' : 'bg-white text-slate-500 ring-1 ring-slate-200') }}">
                            @if ($step['done'])<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" /></svg>@else{{ $index + 1 }}@endif
                        </span>
                        @if ($step['done'])<span class="text-xs font-semibold text-emerald-700">Selesai</span>@elseif ($isNext)<span class="text-xs font-semibold text-accent-700">Berikutnya</span>@else<span class="text-xs font-medium text-slate-400">Menunggu</span>@endif
                    </div>
                    <h4 class="mt-4 text-sm font-semibold text-slate-900">{{ $step['label'] }}</h4>
                    <p class="mt-1 min-h-10 text-xs leading-5 text-slate-500">{{ $step['detail'] }}</p>
                    @if ($isNext)<a href="{{ route($step['route']) }}" class="mt-3 inline-flex min-h-10 items-center text-xs font-semibold text-brand-700 underline decoration-brand-200 underline-offset-4 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500">{{ $step['action'] }} <span class="ml-1" aria-hidden="true">→</span></a>@elseif ($step['done'])<span class="mt-3 inline-flex min-h-10 items-center text-xs font-medium text-emerald-700">Fondasi tersedia</span>@else<span class="mt-3 inline-flex min-h-10 items-center text-xs font-medium text-slate-400">Selesaikan langkah sebelumnya</span>@endif
                </li>
            @endforeach
        </ol>
    </section>

    <section aria-labelledby="admin-metrics-title">
        <div class="mb-3 flex items-center justify-between"><h3 id="admin-metrics-title" class="text-sm font-semibold text-slate-900">Ringkasan {{ $hasPeriod ? 'periode aktif' : 'kesiapan institusi' }}</h3><span class="text-xs text-slate-500">Diperbarui dari data operasional</span></div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                @php($unavailable = ($stat['periodScoped'] ?? false) && ! $hasPeriod)
                <x-card>
                    <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-2xl font-semibold tabular-nums text-brand-900">{{ $unavailable ? '—' : number_format($stat['value']) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $unavailable ? 'Menunggu periode aktif' : $stat['detail'] }}</p>
                </x-card>
            @endforeach
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs" aria-labelledby="class-progress-title">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5"><div><h3 id="class-progress-title" class="font-semibold text-slate-950">Progres nilai per kelas</h3><p class="mt-1 text-sm text-slate-500">Nilai terisi dibanding slot nilai yang diharapkan.</p></div><x-badge variant="accent">{{ $progressByClass->count() }} kelas</x-badge></div>
            <div class="hidden overflow-x-auto md:block"><table class="min-w-[680px] divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><x-table.th>Kelas</x-table.th><x-table.th>Peserta</x-table.th><x-table.th>Nilai terisi</x-table.th><x-table.th>Progress</x-table.th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($progressByClass as $class)<tr><x-table.td><p class="font-semibold text-slate-900">{{ $class->name }}</p><p class="text-xs text-slate-500">{{ $class->code }}</p></x-table.td><x-table.td>{{ $class->student_count }}</x-table.td><x-table.td>{{ $class->filled_grade_count }} / {{ $class->expected_grade_count }}</x-table.td><x-table.td><div class="min-w-40"><div class="flex justify-between text-xs tabular-nums"><span>{{ $class->progress_percent }}%</span><span class="text-slate-500">terisi</span></div><div class="mt-2 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-brand-700" style="width: {{ $class->progress_percent }}%"></div></div></div></x-table.td></tr>@empty<tr><td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">{{ $hasPeriod ? 'Belum ada kelas atau data nilai pada periode aktif.' : 'Aktifkan periode untuk melihat progres nilai per kelas.' }}</td></tr>@endforelse</tbody></table></div>
            <div class="space-y-3 p-4 md:hidden">@forelse ($progressByClass as $class)<article class="rounded-xl border border-slate-200 p-4" data-responsive-card><div class="flex items-start justify-between gap-3"><div><h4 class="font-semibold text-slate-900">{{ $class->name }}</h4><p class="text-xs text-slate-500">{{ $class->code }} · {{ $class->student_count }} peserta</p></div><span class="text-sm font-semibold tabular-nums text-brand-800">{{ $class->progress_percent }}%</span></div><div class="mt-3 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-brand-700" style="width: {{ $class->progress_percent }}%"></div></div><p class="mt-2 text-xs text-slate-500">{{ $class->filled_grade_count }} dari {{ $class->expected_grade_count }} slot nilai terisi</p></article>@empty<p class="px-2 py-8 text-center text-sm text-slate-500">{{ $hasPeriod ? 'Belum ada kelas atau data nilai pada periode aktif.' : 'Aktifkan periode untuk melihat progres nilai per kelas.' }}</p>@endforelse</div>
        </section>
        <section class="rounded-2xl bg-brand-900 p-6 text-white shadow-xs" aria-labelledby="report-status-title"><p id="report-status-title" class="text-sm font-semibold text-accent-300">Status rapor</p><p class="mt-1 text-xs text-slate-400">{{ $hasPeriod ? 'Ringkasan kesiapan rapor pada periode aktif.' : 'Akan terisi setelah periode aktif.' }}</p><div class="mt-5 space-y-4 text-sm"><div class="flex items-center justify-between"><span class="text-slate-300">Belum siap dicetak</span><strong class="tabular-nums">{{ $statusCounts['waiting_review'] }}</strong></div><div class="flex items-center justify-between"><span class="text-slate-300">Siap dicetak</span><strong class="tabular-nums">{{ $statusCounts['approved'] }}</strong></div><div class="flex items-center justify-between"><span class="text-slate-300">Terkunci</span><strong class="tabular-nums">{{ $statusCounts['locked'] }}</strong></div><div class="flex items-center justify-between"><span class="text-slate-300">Belum lengkap</span><strong class="tabular-nums">{{ $statusCounts['revisions'] }}</strong></div></div></section>
    </div>

    <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs" aria-labelledby="quick-links-title"><h3 id="quick-links-title" class="font-semibold text-slate-950">Akses cepat</h3><p class="mt-1 text-sm text-slate-500">Buka pekerjaan yang paling sering dipakai Admin.</p><div class="mt-4 grid gap-2">@foreach ($quickLinks as $link)<x-button href="{{ route($link['route']) }}" variant="{{ $link['variant'] }}" class="justify-start">{{ $link['label'] }}</x-button>@endforeach</div></section>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs" aria-labelledby="recent-transfers-title"><div class="border-b border-slate-100 px-6 py-5"><h3 id="recent-transfers-title" class="font-semibold text-slate-950">Perpindahan terbaru</h3><p class="mt-1 text-sm text-slate-500">Riwayat perpindahan yang sudah disetujui Admin.</p></div><div class="hidden overflow-x-auto md:block"><table class="min-w-[560px] divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><x-table.th>Peserta didik</x-table.th><x-table.th>Asal → tujuan</x-table.th><x-table.th>Tanggal</x-table.th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($recentTransfers as $transfer)<tr><x-table.td>{{ $transfer->student?->full_name ?? '—' }}</x-table.td><x-table.td>{{ $transfer->fromEnrollment?->academicClass?->name ?? '—' }} → {{ $transfer->toEnrollment?->academicClass?->name ?? '—' }}</x-table.td><x-table.td>{{ $transfer->effective_date?->format('d/m/Y') ?? '—' }}</x-table.td></tr>@empty<tr><td colspan="3" class="px-6 py-10 text-center text-sm text-slate-500">{{ $hasPeriod ? 'Belum ada perpindahan terbaru.' : 'Perpindahan akan muncul setelah periode aktif tersedia.' }}</td></tr>@endforelse</tbody></table></div><div class="space-y-3 p-4 md:hidden">@forelse ($recentTransfers as $transfer)<article class="rounded-xl border border-slate-200 p-4" data-responsive-card><p class="font-semibold text-slate-900">{{ $transfer->student?->full_name ?? '—' }}</p><p class="mt-1 text-sm text-slate-600">{{ $transfer->fromEnrollment?->academicClass?->name ?? '—' }} <span class="text-slate-400">→</span> {{ $transfer->toEnrollment?->academicClass?->name ?? '—' }}</p><p class="mt-2 text-xs text-slate-500">{{ $transfer->effective_date?->format('d/m/Y') ?? 'Tanggal belum diisi' }}</p></article>@empty<p class="px-2 py-8 text-center text-sm text-slate-500">{{ $hasPeriod ? 'Belum ada perpindahan terbaru.' : 'Perpindahan akan muncul setelah periode aktif tersedia.' }}</p>@endforelse</div></section>
    </div>
</div>
