@props([
    'title' => 'Terjadi Kesalahan',
    'message' => 'Gagal memuat atau memproses data. Silakan coba kembali beberapa saat lagi.',
    'retryText' => 'Coba Lagi',
    'retryHref' => null,
    'retryClick' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border border-rose-200 bg-rose-50/50 p-8 sm:p-12 text-center']) }}>
    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
        @if (isset($icon))
            {{ $icon }}
        @else
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        @endif
    </div>

    <h3 class="mt-4 text-base font-semibold text-rose-950">{{ $title }}</h3>
    <p class="mt-1.5 max-w-md text-sm text-rose-700/80">{{ $message }}</p>

    @if ($slot->isNotEmpty())
        <div class="mt-4 max-w-lg rounded-lg bg-white/80 p-3 text-left font-mono text-xs text-rose-900 border border-rose-200/60 overflow-x-auto">
            {{ $slot }}
        </div>
    @endif

    @if ($retryText)
        <div class="mt-6">
            @if ($retryHref)
                <x-button variant="danger" size="sm" :href="$retryHref">
                    {{ $retryText }}
                </x-button>
            @elseif ($retryClick)
                <x-button variant="danger" size="sm" :attributes="new \Illuminate\View\ComponentAttributeBag(['@click' => $retryClick])">
                    {{ $retryText }}
                </x-button>
            @else
                <x-button variant="danger" size="sm" onclick="window.location.reload()">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{{ $retryText }}</span>
                </x-button>
            @endif
        </div>
    @elseif (isset($actions))
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            {{ $actions }}
        </div>
    @endif
</div>

