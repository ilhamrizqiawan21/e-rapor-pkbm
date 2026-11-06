<div
    x-data="confirmDialog()"
    x-cloak
    x-show="isOpen"
    @confirm-action.window="openDialog($event)"
    @keydown.escape.window="cancel()"
    class="fixed inset-0 z-[70] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirm-dialog-title"
    aria-describedby="confirm-dialog-message"
>
    <div x-show="isOpen" x-transition:enter="transition-opacity ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/50 backdrop-blur-xs" @click="cancel()"></div>
    <div x-show="isOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="translate-y-2 scale-95 opacity-0" x-transition:enter-end="translate-y-0 scale-100 opacity-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="translate-y-0 scale-100 opacity-100" x-transition:leave-end="translate-y-1 scale-95 opacity-0" class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                    <x-icon name="alert" size="5" />
                </span>
                <div class="min-w-0">
                    <h2 id="confirm-dialog-title" class="text-lg font-semibold text-slate-950">Konfirmasi tindakan</h2>
                    <p id="confirm-dialog-message" class="mt-2 text-sm leading-6 text-slate-600" x-text="message"></p>
                </div>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
            <x-button type="button" variant="cancel" icon="x" x-ref="cancel" @click="cancel()">Batal</x-button>
            <x-button type="button" variant="primary" icon="check" @click="approve()">Lanjutkan</x-button>
        </div>
    </div>
</div>
