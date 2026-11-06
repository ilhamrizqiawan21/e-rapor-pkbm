@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
    'icon' => null,
])

@php
    $baseClasses = 'inline-flex min-h-10 items-center justify-center gap-2 rounded-lg font-medium transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 select-none active:scale-[0.98]';

    $variants = [
        'primary' => 'bg-brand-900 text-white hover:bg-brand-950 focus-visible:ring-brand-700 shadow-xs active:bg-brand-950',
        'secondary' => 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 focus-visible:ring-brand-500 shadow-xs active:bg-slate-100',
        'accent' => 'bg-accent-600 text-white hover:bg-accent-700 focus-visible:ring-accent-500 shadow-xs active:bg-accent-800',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700 focus-visible:ring-emerald-500 shadow-xs active:bg-emerald-800',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 focus-visible:ring-rose-500 shadow-xs active:bg-rose-800',
        'warning' => 'bg-amber-600 text-white hover:bg-amber-700 focus-visible:ring-amber-500 shadow-xs active:bg-amber-800',
        'ghost' => 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 focus-visible:ring-slate-400 active:bg-slate-200',
        'pdf' => 'border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 focus-visible:ring-rose-500 shadow-xs active:bg-rose-200',
        'excel' => 'border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 focus-visible:ring-emerald-500 shadow-xs active:bg-emerald-200',
        'edit' => 'border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 focus-visible:ring-emerald-500 shadow-xs active:bg-emerald-200',
        'cancel' => 'border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 focus-visible:ring-rose-500 shadow-xs active:bg-rose-200',
    ];

    $sizes = [
        'xs' => 'px-2.5 py-1 text-xs gap-1.5',
        'sm' => 'px-3 py-1.5 text-xs gap-1.5',
        'md' => 'px-4 py-2 text-sm gap-2',
        'lg' => 'px-5 py-2.5 text-base gap-2.5',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" />@endif
        {{ $slot }}
    </button>
@endif

