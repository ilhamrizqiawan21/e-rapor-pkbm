@props([
    'striped' => false,
])

<tr {{ $attributes->merge(['class' => 'transition-colors hover:bg-slate-50/80' . ($striped ? ' even:bg-slate-50/50' : '')]) }}>
    {{ $slot }}
</tr>

