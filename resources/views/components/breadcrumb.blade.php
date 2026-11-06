@props([
    'current',
    'parentHref' => null,
    'parentLabel' => null,
])

<nav class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500" aria-label="Breadcrumb">
    <a href="{{ route('dashboard') }}" class="font-medium transition-colors hover:text-brand-800 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">Dashboard</a>
    <x-icon name="chevron-right" size="3" class="text-slate-400" />
    @if ($parentHref && $parentLabel)
        <a href="{{ $parentHref }}" class="font-medium transition-colors hover:text-brand-800 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">{{ $parentLabel }}</a>
        <x-icon name="chevron-right" size="3" class="text-slate-400" />
    @endif
    <span class="font-semibold text-slate-700" aria-current="page">{{ $current }}</span>
</nav>
