@props([
    'institution' => null,
    'logoUrl' => null,
    'size' => 'md',
])

@php
    $brandName = trim((string) ($institution?->name ?: 'E-Rapor'));
    $initials = collect(preg_split('/\s+/', $brandName, -1, PREG_SPLIT_NO_EMPTY) ?: [])
        ->take(2)
        ->map(fn (string $part): string => str($part)->substr(0, 1)->upper())
        ->join('');
    $sizes = [
        'sm' => 'h-8 w-8 rounded-lg text-[10px]',
        'md' => 'h-10 w-10 rounded-xl text-xs',
        'lg' => 'h-14 w-14 rounded-2xl text-sm',
        'xl' => 'h-20 w-20 rounded-2xl text-xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center overflow-hidden bg-accent-400 font-black tracking-tight text-brand-950 shadow-sm '.$sizeClass]) }}>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="Logo {{ $brandName }}" class="h-full w-full object-contain p-1.5">
    @else
        {{ $initials ?: 'ER' }}
    @endif
</span>
