<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'E-Rapor PKBM' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased" x-data="{ mobileSidebarOpen: false }">
    <!-- Responsive Drawer untuk Tablet & Mobile (< lg) -->
    <div
        x-cloak
        x-show="mobileSidebarOpen"
        @keydown.escape.window="mobileSidebarOpen = false"
        class="relative z-50 lg:hidden"
        role="dialog"
        aria-modal="true"
    >
        <!-- Backdrop Overlay -->
        <div
            x-show="mobileSidebarOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"
            @click="mobileSidebarOpen = false"
        ></div>

        <div class="fixed inset-0 flex">
            <div
                x-show="mobileSidebarOpen"
                x-transition:enter="transition ease-in-out duration-300 transform"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-300 transform"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="relative mr-16 flex w-full max-w-xs flex-1 flex-col bg-brand-900 text-white shadow-xl"
            >
                <!-- Tombol Tutup -->
                <div class="absolute top-0 right-0 -mr-12 pt-4">
                    <button
                        type="button"
                        @click="mobileSidebarOpen = false"
                        class="flex h-10 w-10 items-center justify-center rounded-full text-white hover:bg-white/10 focus:outline-hidden"
                        aria-label="Tutup menu"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Konten Navigasi Drawer -->
                <div class="border-b border-white/10 px-6 py-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-accent-300">PKBM</p>
                    <h1 class="mt-2 text-xl font-semibold">E-Rapor</h1>
                    <p class="mt-1 text-sm text-slate-300">Fondasi aplikasi akademik</p>
                </div>
                <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5 text-sm">
                    <a class="block rounded-lg {{ request()->routeIs('dashboard') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('dashboard') }}">Dashboard</a>
                    <a class="block rounded-lg {{ request()->routeIs('components.showcase') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('components.showcase') }}">Komponen UI</a>
                    <span class="block px-3 pt-5 text-xs font-semibold uppercase tracking-wider text-slate-400">Fondasi</span>
                    <span class="block px-3 py-2 text-slate-300">Master Data</span>
                    <span class="block px-3 py-2 text-slate-300">Penilaian</span>
                    <span class="block px-3 py-2 text-slate-300">Rapor dan Leger</span>
                    <span class="block px-3 py-2 text-slate-300">Pengaturan</span>
                </nav>
                <div class="border-t border-white/10 px-6 py-5 text-xs text-slate-300">
                    Laravel {{ app()->version() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Layout Utama -->
    <div class="flex min-h-screen">
        <!-- Sidebar Desktop -->
        <aside class="hidden w-72 shrink-0 bg-brand-900 text-white lg:flex lg:flex-col">
            <div class="border-b border-white/10 px-6 py-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-accent-300">PKBM</p>
                <h1 class="mt-2 text-xl font-semibold">E-Rapor</h1>
                <p class="mt-1 text-sm text-slate-300">Fondasi aplikasi akademik</p>
            </div>
            <nav class="flex-1 space-y-1 px-4 py-5 text-sm">
                <a class="block rounded-lg {{ request()->routeIs('dashboard') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="block rounded-lg {{ request()->routeIs('components.showcase') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('components.showcase') }}">Komponen UI</a>
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

        <!-- Area Konten Utama -->
        <main class="min-w-0 flex-1">
            <!-- Header -->
            <header class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="mobileSidebarOpen = true"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-hidden lg:hidden"
                            aria-label="Buka menu navigasi"
                        >
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div>
                            <p class="text-xs text-slate-500">Periode aktif</p>
                            <p class="text-sm font-semibold text-slate-900">Belum dikonfigurasi</p>
                        </div>
                    </div>
                    <x-badge variant="warning" dot>Fondasi awal</x-badge>
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Global Toast Container -->
    <x-toast />
</body>
</html>
