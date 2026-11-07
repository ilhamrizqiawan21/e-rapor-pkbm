@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'options' => [],
    'placeholder' => null,
    'selected' => null,
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'error' => null,
    'hint' => null,
])

@php
    $selectId = $id ?? ($name ? 'select-' . $name : 'select-' . uniqid());
    $errorMessage = $error ?? (isset($errors) && $name && $errors->has($name) ? $errors->first($name) : null);
    $hasError = !empty($errorMessage);
    $hintId = $selectId . '-hint';
    $errorId = $selectId . '-error';
    $describedBy = $attributes->get('aria-describedby') ?: ($hasError ? $errorId : ($hint ? $hintId : null));

    $selectClasses = 'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 transition focus:outline-hidden disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500';

    if ($multiple) {
        $selectClasses .= ' min-h-40';
    } else {
        $selectClasses .= ' appearance-none pr-10';
    }

    if ($hasError) {
        $selectClasses .= ' border-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 text-rose-900';
    } else {
        $selectClasses .= ' border-slate-300 hover:border-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20';
    }
@endphp

<div class="w-full space-y-1.5">
    @if ($label)
        <label for="{{ $selectId }}" class="block text-sm font-medium text-slate-700">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500 font-semibold">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <select
            @if ($name) name="{{ $name }}" @endif
            id="{{ $selectId }}"
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($required) required @endif
            @if ($disabled) disabled @endif
            @if ($multiple) multiple @endif
            {{ $attributes->merge(['class' => $selectClasses]) }}
        >
            @if ($placeholder)
                <option value="" @if ($selected === null || $selected === '') selected @endif disabled>
                    {{ $placeholder }}
                </option>
            @endif

            @if (!empty($options))
                @foreach ($options as $key => $optionLabel)
                    @php
                        $val = is_array($optionLabel) ? ($optionLabel['id'] ?? $key) : $key;
                        $text = is_array($optionLabel) ? ($optionLabel['name'] ?? $optionLabel['label'] ?? $val) : $optionLabel;
                        $isSelected = (string)$selected === (string)$val;
                    @endphp
                    <option value="{{ $val }}" @if ($isSelected) selected @endif>
                        {{ $text }}
                    </option>
                @endforeach
            @else
                {{ $slot }}
            @endif
        </select>

        @unless ($multiple)
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        @endunless
    </div>

    @if ($hasError)
        <p id="{{ $errorId }}" class="flex items-center gap-1 text-xs text-rose-600" role="alert">
            <svg aria-hidden="true" class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $hintId }}" class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
