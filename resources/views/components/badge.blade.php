@props([
    'variant' => 'neutral',
    'size' => 'sm',
    'dot' => false,
])

@php
    $normalized = strtoupper(trim($variant));

    // Map common domain statuses to semantic color tokens
    $statusMap = [
        'DRAFT' => 'neutral',
        'SUBMITTED' => 'info',
        'APPROVED' => 'success',
        'REVISED' => 'danger',
        'LOCKED' => 'warning',
        'ACTIVE' => 'success',
        'INACTIVE' => 'neutral',
    ];

    $resolvedVariant = $statusMap[$normalized] ?? strtolower($variant);

    $variantClasses = [
        'neutral' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-600/10',
        'brand' => 'bg-brand-50 text-brand-900 ring-1 ring-brand-700/15',
        'accent' => 'bg-accent-50 text-accent-700 ring-1 ring-accent-700/15',
        'success' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/15',
        'warning' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/15',
        'danger' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/15',
        'info' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-600/15',
    ];

    $dotColors = [
        'neutral' => 'bg-slate-400',
        'brand' => 'bg-brand-600',
        'accent' => 'bg-accent-600',
        'success' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-rose-500',
        'info' => 'bg-sky-500',
    ];

    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-xs font-semibold',
    ];

    $badgeClass = 'inline-flex items-center gap-1.5 font-medium rounded-full '
        . ($variantClasses[$resolvedVariant] ?? $variantClasses['neutral']) . ' '
        . ($sizeClasses[$size] ?? $sizeClasses['sm']);

    $dotClass = 'h-1.5 w-1.5 rounded-full ' . ($dotColors[$resolvedVariant] ?? $dotColors['neutral']);

    // Default label if slot is empty and status was given
    $defaultLabels = [
        'DRAFT' => 'Draft',
        'SUBMITTED' => 'Diajukan',
        'APPROVED' => 'Disetujui',
        'REVISED' => 'Perbaikan',
        'LOCKED' => 'Terkunci',
        'ACTIVE' => 'Aktif',
        'INACTIVE' => 'Nonaktif',
    ];
@endphp

<span {{ $attributes->merge(['class' => $badgeClass]) }}>
    @if ($dot)
        <span class="{{ $dotClass }}"></span>
    @endif
    {{ $slot->isEmpty() ? ($defaultLabels[$normalized] ?? $variant) : $slot }}
</span>

