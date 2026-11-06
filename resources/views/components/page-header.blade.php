@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'backHref' => null,
    'backText' => 'Kembali',
])

<header class="mb-6 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
    <div class="max-w-3xl">
        <nav class="mb-4 flex flex-wrap items-center gap-1.5 text-xs text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('dashboard') }}" class="font-medium transition-colors hover:text-brand-800 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">Dashboard</a>
            <x-icon name="chevron-right" size="3" class="text-slate-400" />
            @if ($backHref)
                <a href="{{ $backHref }}" class="font-medium transition-colors hover:text-brand-800 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">{{ str($backText)->replaceStart('Kembali ke ', '') }}</a>
                <x-icon name="chevron-right" size="3" class="text-slate-400" />
            @endif
            <span class="font-semibold text-slate-700" aria-current="page">{{ $title }}</span>
        </nav>
        <div class="mb-4"><x-breadcrumb :current="$title" :parent-href="$backHref" :parent-label="$backHref ? str($backText)->replaceStart('Kembali ke ', '') : null" /></div>
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
