<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Masuk · E-Rapor PKBM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-accent-700">PKBM</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-brand-950">E-Rapor</h1>
                <p class="mt-2 text-sm text-slate-500">Pengelolaan rapor Peserta Didik PKBM</p>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                    {{ session('status') }}
                </div>
            @endif

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

            <p class="mt-6 text-center text-xs text-slate-500">{{ config('app.name') }} · Laravel {{ app()->version() }}</p>
        </div>
    </main>
</body>
</html>
