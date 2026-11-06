@props([
    'title' => 'Belum ada data',
    'description' => 'Tidak ada informasi yang dapat ditampilkan saat ini.',
    'actionText' => null,
    'actionHref' => null,
    'actionClick' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white/50 p-8 sm:p-12 text-center']) }}>
    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
        @if (isset($icon))
            {{ $icon }}
        @else
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
        @endif
    </div>

    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $title }}</h3>
    <p class="mt-1.5 max-w-sm text-sm text-slate-500">{{ $description }}</p>

    @if ($actionText)
        <div class="mt-6">
            @if ($actionHref)
                <x-button variant="primary" size="sm" :href="$actionHref">
                    {{ $actionText }}
                </x-button>
            @elseif ($actionClick)
                <x-button variant="primary" size="sm" :attributes="new \Illuminate\View\ComponentAttributeBag([$actionClick => 'true'])">
                    {{ $actionText }}
                </x-button>
            @else
                <x-button variant="primary" size="sm">
                    {{ $actionText }}
                </x-button>
            @endif
        </div>
    @elseif (isset($actions))
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            {{ $actions }}
        </div>
    @endif
</div>

