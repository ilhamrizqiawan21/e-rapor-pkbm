@props([
    'name',
    'title' => null,
    'maxWidth' => 'md',
])

@php
    $maxWidthClass = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '4xl' => 'sm:max-w-4xl',
    ][$maxWidth] ?? 'sm:max-w-md';
@endphp

<div
    x-data="{ show: false }"
    x-show="show"
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
    x-on:keydown.escape.window="show = false"
    x-cloak
    class="relative z-50"
    role="dialog"
    aria-modal="true"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
        @click="show = false"
    ></div>

    <!-- Modal Container -->
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 lg:p-8">
        <div class="flex min-h-full items-end justify-center text-center sm:items-center sm:p-0">
            <div
                x-show="show"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                @click.stop
                class="relative w-full transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 {{ $maxWidthClass }}"
            >
                @if ($title || isset($header))
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-900">
                            {{ $header ?? $title }}
                        </h3>
                        <button
                            type="button"
                            @click="show = false"
                            class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-hidden"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                <div class="px-6 py-5 text-sm text-slate-600">
                    {{ $slot }}
                </div>

                @if (isset($footer))
                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-3.5">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

