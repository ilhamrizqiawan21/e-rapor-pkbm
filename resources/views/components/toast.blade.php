@props(['messages' => []])

<style>
    @keyframes toast-progress {
        from { transform: scaleX(1); }
        to { transform: scaleX(0); }
    }
</style>

<div
    x-data="{
        toasts: [],
        titles: { success: 'Berhasil', danger: 'Gagal', error: 'Gagal', warning: 'Perhatian', info: 'Informasi' },
        add(toast) {
            const id = Date.now() + Math.random().toString(36).substring(2, 9);
            const newToast = {
                id,
                type: toast.type || 'info',
                title: toast.title || this.titles[toast.type || 'info'],
                message: toast.message || '',
                timeout: toast.timeout || 4000
            };
            this.toasts.push(newToast);

            if (newToast.timeout > 0) {
                setTimeout(() => {
                    this.remove(id);
                }, newToast.timeout);
            }
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    x-init="
        @foreach ($messages as $message)
            add(@js($message));
        @endforeach
    "
    @toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-0 z-50 flex flex-col items-end gap-2 p-4 sm:p-6"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto relative w-full max-w-sm overflow-hidden rounded-2xl border bg-white p-4 shadow-xl shadow-slate-900/10 ring-1 ring-black/5"
            :class="{
                'border-emerald-200': toast.type === 'success',
                'border-rose-200': toast.type === 'danger' || toast.type === 'error',
                'border-amber-200': toast.type === 'warning',
                'border-sky-200': toast.type === 'info'
            }"
            :role="toast.type === 'danger' || toast.type === 'error' ? 'alert' : 'status'"
        >
            <div class="flex items-start gap-3">
                <!-- Status Icon -->
                <div class="shrink-0 pt-0.5">
                <template x-if="toast.type === 'success'">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </template>
                <template x-if="toast.type === 'danger' || toast.type === 'error'">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </template>
                <template x-if="toast.type === 'warning'">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </template>
                <template x-if="toast.type === 'info'">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </template>
                </div>

                <!-- Content -->
                <div class="min-w-0 flex-1">
                    <h4 class="text-sm font-semibold text-slate-950" x-text="toast.title"></h4>
                    <p class="mt-0.5 text-sm leading-5 text-slate-600" x-text="toast.message"></p>
                </div>

                <!-- Dismiss Button -->
                <button
                    type="button"
                    @click="remove(toast.id)"
                    class="shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500"
                    aria-label="Tutup notifikasi"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mt-3 h-1 origin-left rounded-full bg-slate-100">
                <div class="h-full origin-left rounded-full" :class="{
                    'bg-emerald-500': toast.type === 'success',
                    'bg-rose-500': toast.type === 'danger' || toast.type === 'error',
                    'bg-amber-500': toast.type === 'warning',
                    'bg-sky-500': toast.type === 'info'
                }" :style="`animation: toast-progress linear forwards; animation-duration: ${toast.timeout}ms`"></div>
            </div>
        </div>
    </template>
</div>

