@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'rows' => 3,
    'placeholder' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'hint' => null,
])

@php
    $textareaId = $id ?? ($name ? 'textarea-' . $name : 'textarea-' . uniqid());
    $errorMessage = $error ?? (isset($errors) && $name && $errors->has($name) ? $errors->first($name) : null);
    $hasError = !empty($errorMessage);

    $textareaClasses = 'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 transition focus:outline-hidden disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500';

    if ($hasError) {
        $textareaClasses .= ' border-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 text-rose-900';
    } else {
        $textareaClasses .= ' border-slate-300 hover:border-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20';
    }
@endphp

<div class="w-full space-y-1.5">
    @if ($label)
        <label for="{{ $textareaId }}" class="block text-sm font-medium text-slate-700">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500 font-semibold">*</span>
            @endif
        </label>
    @endif

    <textarea
        @if ($name) name="{{ $name }}" @endif
        id="{{ $textareaId }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        @if ($readonly) readonly @endif
        {{ $attributes->merge(['class' => $textareaClasses]) }}
    >{{ $value ?? $slot }}</textarea>

    @if ($hasError)
        <p class="text-xs text-rose-600 flex items-center gap-1">
            <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
