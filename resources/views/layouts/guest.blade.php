<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? (($brandingInstitution?->name ?: 'PKBM').' · E-Rapor') }}</title>
    <link rel="icon" href="{{ $brandingLogoUrl ?: asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ $brandingLogoUrl ?: asset('favicon.svg') }}">
    @include('layouts.partials.font-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $flashToasts = collect([
        ['key' => 'success', 'type' => 'success', 'title' => 'Berhasil'],
        ['key' => 'status', 'type' => 'success', 'title' => 'Berhasil'],
        ['key' => 'error', 'type' => 'danger', 'title' => 'Gagal'],
        ['key' => 'warning', 'type' => 'warning', 'title' => 'Perhatian'],
        ['key' => 'info', 'type' => 'info', 'title' => 'Informasi'],
    ])->map(function (array $flash): ?array {
        $message = session()->pull($flash['key']);

        return filled($message) ? ['type' => $flash['type'], 'title' => $flash['title'], 'message' => $message] : null;
    })->filter()->values()->all();
@endphp
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <x-brand-mark :institution="$brandingInstitution" :logo-url="$brandingLogoUrl" size="xl" class="mx-auto" />
                <p class="mt-4 text-xs font-semibold uppercase tracking-[0.22em] text-accent-700">{{ $brandingInstitution?->name ?: 'PKBM' }}</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-brand-950">E-Rapor</h1>
                <p class="mt-2 text-sm text-slate-500">Pengelolaan rapor Peserta Didik PKBM</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert" tabindex="-1">
                    <p class="font-semibold">Periksa kembali data yang diisi.</p>
                    <ul class="mt-1 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')

            <p class="mt-6 text-center text-xs text-slate-500">{{ config('app.name') }}</p>
        </div>
    </main>
    <x-toast :messages="$flashToasts" />
</body>
</html>
