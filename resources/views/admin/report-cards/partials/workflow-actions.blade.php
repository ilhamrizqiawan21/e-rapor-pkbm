@php
    $workflowUser = auth()->user();
    $canExport = $workflowUser?->can('export', $reportCard) ?? false;
    $canLock = $workflowUser?->can('lock', $reportCard) ?? false;
    $canUnlock = $workflowUser?->can('unlock', $reportCard) ?? false;
    $readyToPrint = (bool) ($progress['complete'] ?? false);
    $statusLabel = $reportCard->status === 'LOCKED'
        ? 'Terkunci'
        : ($readyToPrint ? 'Siap dicetak' : 'Belum lengkap');
    $statusVariant = $reportCard->status === 'LOCKED'
        ? 'LOCKED'
        : ($readyToPrint ? 'success' : 'warning');
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" x-data="{ unlockOpen: false }">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Tahap akhir</p>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h3 class="text-xl font-semibold text-slate-950">Terbitkan rapor</h3>
                <x-badge :variant="$statusVariant" dot>{{ $statusLabel }}</x-badge>
            </div>
            <p class="mt-2 text-sm text-slate-600">Setelah checklist wajib lengkap, pratinjau dan cetak dokumen final dari bagian ini.</p>
        </div>
        @if (app('router')->has('admin.audit-logs.index'))
            <x-button href="{{ route('admin.audit-logs.index', ['entity_type' => 'report_card', 'entity_id' => $reportCard->id]) }}" size="sm" variant="secondary" icon="audit">Lihat histori audit</x-button>
        @endif
    </div>

    <div class="mt-5 rounded-xl border {{ $readyToPrint ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} px-4 py-3">
        @if ($readyToPrint)
            <p class="text-sm font-semibold text-emerald-800">Rapor sudah lengkap dan siap diterbitkan.</p>
            <p class="mt-1 text-xs text-emerald-700">Tidak perlu diajukan atau menunggu persetujuan Kepala PKBM.</p>
        @else
            <p class="text-sm font-semibold text-amber-900">Rapor belum dapat dicetak.</p>
            <p class="mt-1 text-xs text-amber-800">Lengkapi: {{ collect($progress['missing'] ?? [])->pluck('label')->implode(', ') ?: 'komponen wajib rapor' }}.</p>
        @endif
    </div>

    <div class="mt-5 flex flex-wrap gap-2">
        @if ($readyToPrint && $canExport)
            <x-button href="{{ route('admin.report-cards.pdf.preview', $reportCard) }}" size="sm" variant="detail" icon="eye" target="_blank" rel="noopener">Pratinjau PDF</x-button>
            <x-button href="{{ route('admin.report-cards.pdf.download', $reportCard) }}" size="sm" variant="pdf" icon="pdf">Unduh PDF</x-button>
            <x-button href="{{ route('admin.report-cards.pdf.print', $reportCard) }}" size="sm" variant="pdf" icon="print" target="_blank" rel="noopener">Cetak PDF</x-button>
        @elseif (! $canExport)
            <p class="text-sm text-slate-500">Hanya Admin, Kepala PKBM, dan wali kelas yang dapat menerbitkan rapor.</p>
        @endif

        @if ($canLock && $readyToPrint)
            <form method="POST" action="{{ route('admin.report-cards.lock', $reportCard) }}">
                @csrf
                <x-button type="submit" variant="secondary" icon="lock" confirm="Arsipkan rapor ini sebagai salinan final? Pengarsipan tidak diperlukan untuk mencetak.">Arsipkan rapor</x-button>
            </form>
        @endif

        @if ($canUnlock)
            <button type="button" @click="unlockOpen = true" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900 shadow-sm transition-colors hover:bg-amber-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-amber-500"><x-icon name="unlock" />Buka arsip</button>
        @endif
    </div>

    @if ($canUnlock)
        <div x-show="unlockOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="unlock-title-{{ $reportCard->id }}">
            <div @click.outside="unlockOpen = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div><h4 id="unlock-title-{{ $reportCard->id }}" class="text-lg font-semibold text-slate-950">Buka arsip rapor</h4><p class="mt-1 text-sm text-slate-600">Rapor kembali dapat diperbarui. Alasan dicatat di histori audit.</p></div>
                    <button type="button" @click="unlockOpen = false" class="rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-100" aria-label="Tutup modal">×</button>
                </div>
                <form method="POST" action="{{ route('admin.report-cards.unlock', $reportCard) }}" class="mt-5 space-y-4">
                    @csrf
                    <div><label for="unlock-reason-{{ $reportCard->id }}" class="block text-sm font-semibold text-slate-800">Alasan membuka arsip</label><textarea id="unlock-reason-{{ $reportCard->id }}" name="reason" required minlength="3" maxlength="2000" rows="4" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20" placeholder="Contoh: Koreksi nomor dokumen sebelum dicetak."></textarea></div>
                    <div class="flex justify-end gap-2"><x-button type="button" @click="unlockOpen = false" variant="cancel" icon="x">Batal</x-button><x-button type="submit" variant="warning" icon="unlock" confirm="Buka arsip rapor ini agar dapat diperbarui?">Buka arsip</x-button></div>
                </form>
            </div>
        </div>
    @endif
</section>
