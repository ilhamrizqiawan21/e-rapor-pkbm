@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'backHref' => null,
    'backText' => 'Kembali',
])

<header class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
    <div class="max-w-3xl">
        @if ($backHref)
            <a href="{{ $backHref }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-950 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 18-6-6 6-6" /></svg>
                {{ $backText }}
            </a>
        @endif
        @if ($eyebrow)
            <p class="{{ $backHref ? 'mt-5' : '' }} text-xs font-bold uppercase tracking-[0.22em] text-accent-700">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $description }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</header>
