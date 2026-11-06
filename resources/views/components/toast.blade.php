<div
    x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now() + Math.random().toString(36).substring(2, 9);
            const newToast = {
                id,
                type: toast.type || 'info',
                title: toast.title || null,
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
        @if (session()->has('success'))
            add({ type: 'success', message: {{ json_encode(session('success')) }} });
        @endif
        @if (session()->has('error'))
            add({ type: 'danger', message: {{ json_encode(session('error')) }} });
        @endif
        @if (session()->has('warning'))
            add({ type: 'warning', message: {{ json_encode(session('warning')) }} });
        @endif
        @if (session()->has('info'))
            add({ type: 'info', message: {{ json_encode(session('info')) }} });
        @endif
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
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border bg-white p-4 shadow-lg ring-1 ring-black/5"
            :class="{
                'border-emerald-200': toast.type === 'success',
                'border-rose-200': toast.type === 'danger' || toast.type === 'error',
                'border-amber-200': toast.type === 'warning',
                'border-sky-200': toast.type === 'info'
            }"
        >
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
            <div class="flex-1 min-w-0">
                <template x-if="toast.title">
                    <h4 class="text-sm font-semibold text-slate-900" x-text="toast.title"></h4>
                </template>
                <p class="text-sm text-slate-600" x-text="toast.message"></p>
            </div>

            <!-- Dismiss Button -->
            <button
                type="button"
                @click="remove(toast.id)"
                class="shrink-0 rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-hidden"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>

