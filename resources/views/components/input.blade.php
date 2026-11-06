@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'placeholder' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'hint' => null,
])

@php
    $inputId = $id ?? ($name ? 'input-' . $name : 'input-' . uniqid());
    $errorMessage = $error ?? (isset($errors) && $name && $errors->has($name) ? $errors->first($name) : null);
    $hasError = !empty($errorMessage);
    $hintId = $inputId . '-hint';
    $errorId = $inputId . '-error';
    $describedBy = $attributes->get('aria-describedby') ?: ($hasError ? $errorId : ($hint ? $hintId : null));

    $inputClasses = 'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 transition focus:outline-hidden disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500';

    if ($hasError) {
        $inputClasses .= ' border-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 text-rose-900';
    } else {
        $inputClasses .= ' border-slate-300 hover:border-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20';
    }
@endphp

<div class="w-full space-y-1.5">
    @if ($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-slate-700">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500 font-semibold">*</span>
            @endif
        </label>
    @endif

    <div class="relative" @if ($type === 'password') x-data="{ showPassword: false }" @endif>
        @if (isset($leadingIcon))
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                {{ $leadingIcon }}
            </div>
        @endif

        <input
            @if ($type === 'password') x-bind:type="showPassword ? 'text' : 'password'" @else type="{{ $type }}" @endif
            @if ($name) name="{{ $name }}" @endif
            id="{{ $inputId }}"
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($value !== null) value="{{ $value }}" @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($required) required @endif
            @if ($disabled) disabled @endif
            @if ($readonly) readonly @endif
            {{ $attributes->merge(['class' => $inputClasses . (isset($leadingIcon) ? ' pl-10' : '') . (isset($trailingIcon) || $type === 'password' ? ' pr-28' : '')]) }}
        />

        @if ($type === 'password')
            <button
                type="button"
                @click="showPassword = !showPassword"
                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                :aria-pressed="showPassword.toString()"
                class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-xs font-semibold text-brand-700 hover:text-brand-950 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500"
            >
                <span x-text="showPassword ? 'Sembunyikan' : 'Tampilkan'"></span>
            </button>
        @endif

        @if (isset($trailingIcon))
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                {{ $trailingIcon }}
            </div>
        @endif
    </div>

    @if ($hasError)
        <p id="{{ $inputId }}-error" class="text-xs text-rose-600 flex items-center gap-1" role="alert">
            <svg aria-hidden="true" class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $hintId }}" class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
