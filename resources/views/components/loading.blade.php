@props([
    'text' => 'Memuat data...',
    'size' => 'md',
    'inline' => false,
    'overlay' => false,
])

@php
    $spinnerSizes = [
        'sm' => 'h-4 w-4 border-2',
        'md' => 'h-7 w-7 border-3',
        'lg' => 'h-10 w-10 border-4',
    ][$size] ?? 'h-7 w-7 border-3';

    $textSizes = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
        'lg' => 'text-base font-medium',
    ][$size] ?? 'text-sm';
@endphp

@if ($overlay)
    <div {{ $attributes->merge(['class' => 'absolute inset-0 z-40 flex flex-col items-center justify-center rounded-xl bg-white/80 backdrop-blur-xs']) }}>
        <div class="{{ $spinnerSizes }} animate-spin rounded-full border-brand-200 border-t-brand-600"></div>
        @if ($text)
            <p class="mt-3 {{ $textSizes }} text-slate-600 font-medium">{{ $text }}</p>
        @endif
    </div>
@elseif ($inline)
    <div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
        <div class="{{ $spinnerSizes }} animate-spin rounded-full border-brand-200 border-t-brand-600"></div>
        @if ($text)
            <span class="{{ $textSizes }} text-slate-600">{{ $text }}</span>
        @endif
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center p-8 text-center']) }}>
        <div class="{{ $spinnerSizes }} animate-spin rounded-full border-brand-200 border-t-brand-600"></div>
        @if ($text)
            <p class="mt-3 {{ $textSizes }} text-slate-600">{{ $text }}</p>
        @endif
    </div>
@endif

