<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'E-Rapor PKBM' }}</title>
    @include('layouts.partials.font-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $activePeriodLabel = data_get($period ?? null, 'label', 'Periode belum aktif');
    $periodReady = (bool) ($hasPeriod ?? false);
    $currentUser = auth()->user();
    $currentRoleLabel = $currentUser?->roles->map(fn ($role) => $role->code->label())->join(', ') ?: 'Pengguna';
    $currentUserInitials = collect(str($currentUser?->name ?? 'ER')->explode(' '))
        ->filter()
        ->take(2)
        ->map(fn ($part) => str($part)->substr(0, 1)->upper())
        ->join('');
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
        <aside class="sticky top-0 hidden h-screen max-h-screen shrink-0 flex-col bg-brand-900 text-white transition-[width] duration-200 lg:flex" :class="sidebarCollapsed ? 'w-[88px]' : 'w-72'" :aria-label="sidebarCollapsed ? 'Sidebar ringkas' : 'Sidebar utama'">
            <div class="flex items-center justify-between border-b border-white/10 px-4 py-5" :class="sidebarCollapsed ? 'flex-col gap-4 px-3' : ''">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 rounded-xl focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent-300" title="E-Rapor PKBM">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent-400 font-black tracking-tight text-brand-950">ER</span>
                    <span x-show="!sidebarCollapsed" x-transition.opacity class="min-w-0"><span class="block text-xs font-semibold uppercase tracking-[0.2em] text-accent-300">PKBM</span><span class="mt-1 block truncate text-lg font-semibold">E-Rapor</span></span>
                </a>
                <button type="button" @click="toggleSidebar()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-300 hover:bg-white/10 hover:text-white focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent-300" :aria-label="sidebarCollapsed ? 'Buka sidebar' : 'Ciutkan sidebar'" :aria-expanded="(!sidebarCollapsed).toString()" title="Menu sidebar">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" /></svg>
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
            <header class="sticky top-0 z-30 border-b border-slate-200/90 bg-white/95 shadow-[0_1px_0_rgba(15,23,42,0.02)] backdrop-blur-md">
                <div class="mx-auto flex min-h-[76px] max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 flex-1 items-center gap-3 lg:flex-none">
                        <button
                            type="button"
                            @click="openMobileSidebar($event)"
                            :aria-expanded="mobileSidebarOpen.toString()"
                            aria-controls="mobile-navigation-drawer"
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition-[background-color,border-color,color,transform] duration-150 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800 active:scale-95 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 lg:hidden"
                            aria-label="Buka menu navigasi"
                            title="Buka menu navigasi"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div class="flex min-w-0 flex-1 items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2 sm:flex-none sm:px-3.5">
                            <span class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-900 text-accent-300 shadow-sm sm:flex" aria-hidden="true">
                                <x-icon name="calendar" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $periodReady ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                    Periode aktif
                                </p>
                                <p class="mt-0.5 max-w-[12rem] truncate text-sm font-semibold text-slate-950 sm:max-w-[18rem]">{{ $activePeriodLabel }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <span class="hidden sm:block"><x-badge :variant="$periodReady ? 'success' : 'warning'" dot>{{ $periodReady ? 'Siap digunakan' : 'Perlu konfigurasi' }}</x-badge></span>
                        @auth
                            <div class="relative" x-data="{ accountOpen: false }">
                                <button
                                    type="button"
                                    @click="accountOpen = !accountOpen"
                                    @click.outside="accountOpen = false"
                                    @keydown.escape.window="accountOpen = false"
                                    :aria-expanded="accountOpen.toString()"
                                    aria-haspopup="menu"
                                    aria-label="Buka menu akun"
                                    title="Menu akun"
                                    class="group inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-1.5 py-1.5 text-left shadow-sm transition-[background-color,border-color,box-shadow,transform] duration-150 hover:border-brand-200 hover:bg-brand-50/50 hover:shadow-md active:scale-[0.98] focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 sm:gap-3 sm:pl-2"
                                >
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-900 text-xs font-bold tracking-wide text-accent-300 shadow-inner" aria-hidden="true">{{ $currentUserInitials ?: 'ER' }}</span>
                                    <span class="hidden min-w-0 sm:block">
                                        <span class="block max-w-[9rem] truncate text-sm font-semibold leading-5 text-slate-950">{{ $currentUser->name }}</span>
                                        <span class="block max-w-[9rem] truncate text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ $currentRoleLabel }}</span>
                                    </span>
                                    <svg class="mr-1 h-4 w-4 text-slate-400 transition-transform duration-150 group-hover:text-brand-700" :class="accountOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                                </button>
                                <div x-cloak x-show="accountOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="translate-y-1 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-1 opacity-0" role="menu" class="absolute right-0 z-30 mt-3 w-[min(19rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-slate-200 bg-white py-2 shadow-xl shadow-slate-900/10 ring-1 ring-black/5">
                                    <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-4">
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-900 text-sm font-bold tracking-wide text-accent-300">{{ $currentUserInitials ?: 'ER' }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-950">{{ $currentUser->name }}</p>
                                            <p class="truncate text-xs text-slate-500">{{ $currentUser->username }}</p>
                                            <p class="mt-1 text-xs font-semibold text-accent-700">Peran: {{ $currentRoleLabel }}</p>
                                        </div>
                                    </div>
                                    <div class="p-2">
                                        <a role="menuitem" href="{{ route('profile.edit') }}" class="flex min-h-10 items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-brand-50 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500"><x-icon name="user" class="h-4 w-4 text-slate-400" />Profil saya</a>
                                        <a role="menuitem" href="{{ route('password.edit') }}" class="flex min-h-10 items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-brand-50 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500"><x-icon name="lock" class="h-4 w-4 text-slate-400" />Ubah password</a>
                                    </div>
                                    <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 px-2 pt-2">
                                        @csrf
                                        <button role="menuitem" type="submit" class="flex min-h-10 w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold text-rose-700 transition-colors duration-150 hover:bg-rose-50 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-rose-500"><x-icon name="logout" class="h-4 w-4" />Keluar dari aplikasi</button>
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

    <x-confirm-dialog />

    <!-- Global Toast Container -->
    <x-toast />
</body>
</html>
