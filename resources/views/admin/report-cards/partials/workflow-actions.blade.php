@php
    $workflowUser = auth()->user();
    $canSubmit = $workflowUser?->can('submit', $reportCard) ?? false;
    $canReview = $workflowUser?->can('review', $reportCard) ?? false;
    $canReject = $workflowUser?->can('reject', $reportCard) ?? false;
    $canApprove = $workflowUser?->can('approve', $reportCard) ?? false;
    $canLock = $workflowUser?->can('lock', $reportCard) ?? false;
    $canUnlock = $workflowUser?->can('unlock', $reportCard) ?? false;
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" x-data="{ unlockOpen: false }">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Workflow rapor</p>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h3 class="text-xl font-semibold text-slate-950">Status dan tindakan</h3>
                <x-badge :variant="$reportCard->status" dot />
            </div>
            <p class="mt-2 text-sm text-slate-600">Setiap perubahan status dicatat dengan actor, waktu, dan alasan bila diperlukan.</p>
        </div>
        @if (app('router')->has('admin.audit-logs.index'))
            <x-button href="{{ route('admin.audit-logs.index', ['entity_type' => 'report_card', 'entity_id' => $reportCard->id]) }}" size="sm" variant="secondary" icon="audit">Lihat histori audit</x-button>
        @endif
    </div>

    <div class="mt-5 flex flex-wrap gap-2">
        @if (app('router')->has('admin.report-cards.pdf.preview') && in_array($reportCard->status, ['APPROVED', 'LOCKED'], true))
            <x-button href="{{ route('admin.report-cards.pdf.preview', $reportCard) }}" size="sm" variant="pdf" icon="eye" target="_blank" rel="noopener">Preview PDF</x-button>
            <x-button href="{{ route('admin.report-cards.pdf.download', $reportCard) }}" size="sm" variant="pdf" icon="pdf">Download PDF</x-button>
            <x-button href="{{ route('admin.report-cards.pdf.print', $reportCard) }}" size="sm" variant="pdf" icon="print" target="_blank" rel="noopener">Cetak PDF</x-button>
        @endif
        @if ($canSubmit)
            <form method="POST" action="{{ route('admin.report-cards.submit', $reportCard) }}">@csrf <x-button type="submit" variant="success" icon="check" confirm="Ajukan rapor ini untuk ditinjau?">Ajukan untuk ditinjau</x-button></form>
        @endif
        @if ($canReview)
            <form method="POST" action="{{ route('admin.report-cards.review', $reportCard) }}">@csrf <x-button type="submit" icon="eye" confirm="Mulai review kelengkapan rapor ini?">Mulai review kelengkapan</x-button></form>
        @endif
        @if ($canApprove)
            <form method="POST" action="{{ route('admin.report-cards.approve', $reportCard) }}">@csrf <x-button type="submit" variant="success" icon="check" confirm="Setujui rapor ini? Pastikan seluruh data sudah benar.">Setujui rapor</x-button></form>
        @endif
        @if ($canLock)
            <form method="POST" action="{{ route('admin.report-cards.lock', $reportCard) }}">@csrf <x-button type="submit" icon="lock" confirm="Kunci rapor ini? Data akan menjadi snapshot dan tidak dapat diedit biasa.">Kunci rapor</x-button></form>
        @endif
        @if ($canReject)
            <form method="POST" action="{{ route('admin.report-cards.reject', $reportCard) }}" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-end">
                @csrf
                <label class="sr-only" for="reject-reason-{{ $reportCard->id }}">Alasan penolakan</label>
                <input id="reject-reason-{{ $reportCard->id }}" name="reason" required minlength="3" maxlength="2000" placeholder="Alasan penolakan" class="w-full rounded-lg border-slate-300 text-sm shadow-sm sm:w-64">
                <x-button type="submit" variant="danger" icon="arrow-left" confirm="Kembalikan rapor ini untuk revisi?">Kembalikan untuk revisi</x-button>
            </form>
        @endif
        @if ($canUnlock)
            <button type="button" @click="unlockOpen = true" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900 shadow-sm transition-colors hover:bg-amber-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-amber-500"><x-icon name="unlock" />Buka kunci</button>
        @endif
        @if (! $canSubmit && ! $canReview && ! $canReject && ! $canApprove && ! $canLock && ! $canUnlock)
            <p class="text-sm text-slate-500">Tidak ada tindakan yang tersedia untuk role dan status ini.</p>
        @endif
    </div>

    <dl class="mt-5 grid gap-3 border-t border-slate-100 pt-4 text-xs text-slate-600 sm:grid-cols-2 lg:grid-cols-4">
        <div><dt class="font-semibold uppercase tracking-wide text-slate-400">Diajukan</dt><dd class="mt-1">{{ $reportCard->submittedBy?->name ?? '—' }}{{ $reportCard->submitted_at ? ' · '.$reportCard->submitted_at->format('d/m/Y H:i') : '' }}</dd></div>
        <div><dt class="font-semibold uppercase tracking-wide text-slate-400">Direview</dt><dd class="mt-1">{{ $reportCard->reviewedBy?->name ?? '—' }}{{ $reportCard->reviewed_at ? ' · '.$reportCard->reviewed_at->format('d/m/Y H:i') : '' }}</dd></div>
        <div><dt class="font-semibold uppercase tracking-wide text-slate-400">Disetujui</dt><dd class="mt-1">{{ $reportCard->approvedBy?->name ?? '—' }}{{ $reportCard->approved_at ? ' · '.$reportCard->approved_at->format('d/m/Y H:i') : '' }}</dd></div>
        <div><dt class="font-semibold uppercase tracking-wide text-slate-400">Dikunci</dt><dd class="mt-1">{{ $reportCard->lockedBy?->name ?? '—' }}{{ $reportCard->locked_at ? ' · '.$reportCard->locked_at->format('d/m/Y H:i') : '' }}</dd></div>
    </dl>

    @if ($reportCard->rejection_reason)
        <p class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800"><span class="font-semibold">Alasan revisi:</span> {{ $reportCard->rejection_reason }}</p>
    @endif
    @if ($reportCard->unlock_reason)
        <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800"><span class="font-semibold">Alasan unlock:</span> {{ $reportCard->unlock_reason }}</p>
    @endif

    @if ($canUnlock)
        <div x-show="unlockOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="unlock-title-{{ $reportCard->id }}">
            <div @click.outside="unlockOpen = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div><h4 id="unlock-title-{{ $reportCard->id }}" class="text-lg font-semibold text-slate-950">Buka kunci rapor</h4><p class="mt-1 text-sm text-slate-600">Rapor akan kembali ke Draft. Alasan wajib diisi dan disimpan di histori audit.</p></div>
                    <button type="button" @click="unlockOpen = false" class="rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-100" aria-label="Tutup modal">×</button>
                </div>
                <form method="POST" action="{{ route('admin.report-cards.unlock', $reportCard) }}" class="mt-5 space-y-4">
                    @csrf
                    <div><label for="unlock-reason-{{ $reportCard->id }}" class="block text-sm font-semibold text-slate-800">Alasan unlock</label><textarea id="unlock-reason-{{ $reportCard->id }}" name="reason" required minlength="3" maxlength="2000" rows="4" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Contoh: Koreksi nomor dokumen sebelum penerbitan."></textarea></div>
                    <div class="flex justify-end gap-2"><x-button type="button" @click="unlockOpen = false" variant="cancel" icon="x">Batal</x-button><x-button type="submit" variant="warning" icon="unlock" confirm="Buka kunci rapor ini dan kembalikan ke Draft?">Buka kunci dan kembali ke Draft</x-button></div>
                </form>
            </div>
        </div>
    @endif
</section>
