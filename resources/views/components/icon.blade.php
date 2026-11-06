@props([
    'name',
    'size' => '4',
])

@php
    $icons = [
        'add' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14" />',
        'alert' => '<circle cx="12" cy="12" r="8.5" stroke-width="1.8" /><path stroke-linecap="round" stroke-width="1.8" d="M12 8v5m0 3h.01" />',
        'arrow-left' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 18-6-6 6-6M9 12h10" />',
        'audit' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm3 4h5M8 12h8M8 16h5" />',
        'check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" />',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2" stroke-width="1.8" /><path stroke-linecap="round" stroke-width="1.8" d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17" />',
        'chevron-right' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 18 6-6-6-6" />',
        'copy' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 8V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-3M6 8h7a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2Z" />',
        'download' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" />',
        'edit' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m14.5 5.5 4 4M5 19l1.2-4.8L16.7 3.7a2.1 2.1 0 0 1 3 3l-10.5 10.5L5 19Z" />',
        'excel' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M6 2.5h8l4 4V21.5H6V2.5Zm8 0v4h4M8.5 11.5l4 5m0-5-4 5M8.5 18.5h5" />',
        'eye' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" /><circle cx="12" cy="12" r="2.5" stroke-width="1.8" />',
        'filter' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M7 12h10m-7 6h4" />',
        'lock' => '<rect x="5" y="10" width="14" height="10" rx="2" stroke-width="1.8" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10V7a4 4 0 0 1 8 0v3" />',
        'pdf' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M6 2.5h8l4 4V21.5H6V2.5Zm8 0v4h4M8.5 12.5h7M8.5 15.5h5" />',
        'plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14" />',
        'print' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2M7 14h10v7H7v-7Z" />',
        'save' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 3h11l3 3v15H5V3Zm3 0v5h7V3M8 21v-7h8v7" />',
        'trash' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M10 11v6m4-6v6M9 7V4h6v3m-9 0 1 13h10l1-13" />',
        'upload' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16V4m0 0 4 4m-4-4L8 8M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" />',
        'unlock' => '<rect x="5" y="10" width="14" height="10" rx="2" stroke-width="1.8" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10V7a4 4 0 0 1 7.5-2" />',
        'logout' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 5h4a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-4M10 8l-4 4 4 4m-4-4h10" />',
        'user' => '<circle cx="12" cy="8" r="3.5" stroke-width="1.8" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 20a7.5 7.5 0 0 1 15 0" />',
        'x' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6 6 18" />',
    ];
@endphp

<svg {{ $attributes->merge(['class' => "h-{$size} w-{$size} shrink-0", 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'aria-hidden' => 'true']) }}>
    {!! $icons[$name] ?? $icons['check'] !!}
</svg>
