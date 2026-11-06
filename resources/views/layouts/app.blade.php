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
@php
    $activePeriodLabel = data_get($period ?? null, 'label', 'Periode belum aktif');
    $periodReady = (bool) ($hasPeriod ?? false);
@endphp
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased" x-data="mobileNavigation()">
    {{-- Mobile drawer mirrors the desktop navigation but keeps touch targets generous. --}}
    <div id="mobile-navigation-drawer" x-cloak x-show="mobileSidebarOpen" @keydown.escape.window="closeMobileSidebar()" @keydown="trapFocus($event)" x-ref="drawer" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-label="Menu navigasi aplikasi" aria-modal="true">
        <div x-show="mobileSidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs" @click="closeMobileSidebar()"></div>
        <div class="fixed inset-0 flex">
            <aside x-show="mobileSidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative mr-16 flex w-full max-w-xs flex-1 flex-col bg-brand-900 text-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-white/10 px-5 py-5">
                    <a href="{{ route('dashboard') }}" @click="closeMobileSidebar()" class="flex items-center gap-3 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent-300">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent-400 font-black tracking-tight text-brand-950">ER</span>
                        <span><span class="block text-xs font-semibold uppercase tracking-[0.2em] text-accent-300">PKBM</span><span class="mt-1 block text-lg font-semibold">E-Rapor</span></span>
                    </a>
                    <button type="button" @click="closeMobileSidebar()" x-ref="closeButton" class="flex h-10 w-10 items-center justify-center rounded-xl text-white hover:bg-white/10 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent-300" aria-label="Tutup menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                @include('layouts.partials.sidebar-navigation', ['mobile' => true])
                <div class="border-t border-white/10 px-6 py-4 text-xs text-slate-400">{{ auth()->user()->name ?? 'E-Rapor PKBM' }}</div>
            </aside>
        </div>
    </div>

    <!-- Layout Utama -->
    <div class="flex min-h-screen">
        <!-- Sidebar Desktop -->
        <aside class="hidden shrink-0 flex-col bg-brand-900 text-white transition-[width] duration-200 lg:flex" :class="sidebarCollapsed ? 'w-[88px]' : 'w-72'" :aria-label="sidebarCollapsed ? 'Sidebar ringkas' : 'Sidebar utama'">
            <div class="flex items-center justify-between border-b border-white/10 px-4 py-5" :class="sidebarCollapsed ? 'flex-col gap-4 px-3' : ''">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 rounded-xl focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent-300" title="E-Rapor PKBM">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent-400 font-black tracking-tight text-brand-950">ER</span>
                    <span x-show="!sidebarCollapsed" x-transition.opacity class="min-w-0"><span class="block text-xs font-semibold uppercase tracking-[0.2em] text-accent-300">PKBM</span><span class="mt-1 block truncate text-lg font-semibold">E-Rapor</span></span>
                </a>
                <button type="button" @click="toggleSidebar()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-300 hover:bg-white/10 hover:text-white focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent-300" :aria-label="sidebarCollapsed ? 'Buka sidebar' : 'Ciutkan sidebar'" :aria-expanded="(!sidebarCollapsed).toString()" title="Toggle sidebar">
                    <svg class="h-4 w-4 transition-transform" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 18-6-6 6-6" /></svg>
                </button>
            </div>
            @include('layouts.partials.sidebar-navigation', ['mobile' => false])
            <div class="border-t border-white/10 px-4 py-4 text-xs text-slate-400" :class="sidebarCollapsed ? 'px-3 text-center' : ''">
                <span x-show="!sidebarCollapsed">Administrasi akademik PKBM</span>
                <span x-show="sidebarCollapsed" class="text-accent-300">PKBM</span>
            </div>
        </aside>

        <!-- Area Konten Utama -->
        <main class="min-w-0 flex-1" x-bind:inert="mobileSidebarOpen" x-bind:aria-hidden="mobileSidebarOpen ? 'true' : 'false'">
            <!-- Header -->
            <header class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="openMobileSidebar($event)"
                            :aria-expanded="mobileSidebarOpen.toString()"
                            aria-controls="mobile-navigation-drawer"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 lg:hidden"
                            aria-label="Buka menu navigasi"
                        >
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div>
                            <p class="text-xs text-slate-500">Periode aktif</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $activePeriodLabel }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 sm:gap-4">
                        <x-badge :variant="$periodReady ? 'success' : 'warning'" dot>{{ $periodReady ? 'Periode aktif' : 'Perlu konfigurasi' }}</x-badge>
                        @auth
                            <div class="hidden text-right sm:block">
                                <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500">{{ auth()->user()->staffProfile?->title ?? 'Pengguna' }}</p>
                                <p class="text-[11px] font-medium text-accent-700">Role: {{ auth()->user()->roles->map(fn ($role) => $role->code->label())->join(', ') ?: 'Pengguna' }}</p>
                            </div>
                            <div class="relative" x-data="{ accountOpen: false }">
                                <button
                                    type="button"
                                    @click="accountOpen = !accountOpen"
                                    @click.outside="accountOpen = false"
                                    :aria-expanded="accountOpen.toString()"
                                    aria-haspopup="menu"
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500"
                                >
                                    <span class="hidden sm:inline">Akun</span>
                                    <span class="sm:hidden">{{ str(auth()->user()->name)->limit(12) }}</span>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                                </button>
                                <div x-cloak x-show="accountOpen" x-transition role="menu" class="absolute right-0 z-30 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white py-2 shadow-lg">
                                    <div class="border-b border-slate-100 px-4 py-3">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ auth()->user()->username }}</p>
                                        <p class="mt-1 text-xs font-medium text-accent-700">{{ auth()->user()->roles->map(fn ($role) => $role->code->label())->join(', ') ?: 'Pengguna' }}</p>
                                    </div>
                                    <a role="menuitem" href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profil saya</a>
                                    <a role="menuitem" href="{{ route('password.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Ubah password</a>
                                    <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 pt-2">
                                        @csrf
                                        <button role="menuitem" type="submit" class="block w-full px-4 py-2 text-left text-sm font-medium text-rose-700 hover:bg-rose-50">Keluar</button>
                                    </form>
                                </div>
                            </div>
                        @endauth
                    </div>
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert" tabindex="-1">
                        <p class="font-semibold">Periksa kembali data yang diisi.</p>
                        <ul class="mt-1 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Global Toast Container -->
    <x-toast />
</body>
</html>
