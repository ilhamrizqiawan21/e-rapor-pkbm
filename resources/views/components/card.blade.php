@props([
    'variant' => 'surface',
    'padding' => 'md',
])

@php
    $variantClasses = [
        'surface' => 'rounded-2xl border border-slate-200 bg-white shadow-xs',
        'subtle' => 'rounded-2xl border border-slate-200 bg-slate-50/70',
        'brand' => 'rounded-2xl bg-brand-900 text-white shadow-xs',
    ];

    $paddingClasses = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-5 sm:p-6',
        'lg' => 'p-6 sm:p-8',
    ];

    $classes = trim(($variantClasses[$variant] ?? $variantClasses['surface']).' '.($paddingClasses[$padding] ?? $paddingClasses['md']));
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
