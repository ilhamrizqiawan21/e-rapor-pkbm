@props([
    'head' => null,
])

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
    <div class="overflow-x-auto">
        <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600']) }}>
            @if ($head)
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-600">
                    {{ $head }}
                </thead>
            @endif
            <tbody class="divide-y divide-slate-200 bg-white">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>

