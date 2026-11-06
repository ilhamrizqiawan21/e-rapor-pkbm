<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'E-Rapor PKBM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-72 shrink-0 bg-[#123B52] text-white lg:flex lg:flex-col">
            <div class="border-b border-white/10 px-6 py-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-200">PKBM</p>
                <h1 class="mt-2 text-xl font-semibold">E-Rapor</h1>
                <p class="mt-1 text-sm text-slate-300">Fondasi aplikasi akademik</p>
            </div>
            <nav class="flex-1 space-y-1 px-4 py-5 text-sm">
                <a class="block rounded-lg bg-white/10 px-3 py-2 font-medium" href="{{ route('dashboard') }}">Dashboard</a>
                <span class="block px-3 pt-5 text-xs font-semibold uppercase tracking-wider text-slate-400">Fondasi</span>
                <span class="block px-3 py-2 text-slate-300">Master Data</span>
                <span class="block px-3 py-2 text-slate-300">Penilaian</span>
                <span class="block px-3 py-2 text-slate-300">Rapor dan Leger</span>
                <span class="block px-3 py-2 text-slate-300">Pengaturan</span>
            </nav>
            <div class="border-t border-white/10 px-6 py-5 text-xs text-slate-300">
                Laravel {{ app()->version() }}
            </div>
        </aside>

        <main class="min-w-0 flex-1">
            <header class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
                    <div>
                        <p class="text-sm text-slate-500">Periode aktif</p>
                        <p class="font-semibold text-slate-900">Belum dikonfigurasi</p>
                    </div>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Fondasi awal</span>
                </div>
            </header>
            <div class="mx-auto max-w-7xl px-5 py-8 sm:px-8">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
