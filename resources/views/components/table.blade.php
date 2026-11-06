@props([
    'head' => null,
])

<x-table.index :head="$head" {{ $attributes }}>
    {{ $slot }}
</x-table.index>

