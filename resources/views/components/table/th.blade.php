@props([
    'align' => 'left',
])

@php
    $alignClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
    ][$align] ?? 'text-left';
@endphp

<th scope="col" {{ $attributes->merge(['class' => 'px-4 py-3.5 text-xs font-semibold text-slate-700 uppercase tracking-wider ' . $alignClasses]) }}>
    {{ $slot }}
</th>

