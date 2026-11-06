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
    <!-- Responsive Drawer untuk Tablet & Mobile (< lg) -->
    <div
        x-cloak
        x-show="mobileSidebarOpen"
        @keydown.escape.window="closeMobileSidebar()"
        @keydown="trapFocus($event)"
        x-ref="drawer"
        class="relative z-50 lg:hidden"
        role="dialog"
        aria-label="Menu navigasi aplikasi"
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
                        @click="closeMobileSidebar()"
                        x-ref="closeButton"
                        class="flex h-10 w-10 items-center justify-center rounded-full text-white hover:bg-white/10 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white"
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
                    <p class="mt-1 text-sm text-slate-300">Administrasi akademik PKBM</p>
                </div>
                <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5 text-sm" @click="closeMobileSidebar()">
                    <a class="block rounded-lg {{ request()->routeIs('dashboard*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard*')) aria-current="page" @endif>Dashboard</a>
                    <span class="block px-3 pt-5 text-xs font-semibold uppercase tracking-wider text-slate-400">Fondasi data inti</span>
                    <span class="block px-3 py-2 text-slate-300">Master Data</span>
                    @auth
                        @if (auth()->user()->hasRole('ADMIN'))
                            <a class="block rounded-lg {{ request()->routeIs('admin.teachers.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.teachers.index') }}">Data Guru</a>
                        @endif
                        <a class="block rounded-lg {{ request()->routeIs('admin.students.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.students.index') }}">Peserta didik</a>
                        <a class="block rounded-lg {{ request()->routeIs('admin.subjects.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.subjects.index') }}">Mata pelajaran</a>
                        <a class="block rounded-lg {{ request()->routeIs('admin.description-snippets.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.description-snippets.index') }}">Bank deskripsi</a>
                        @if (auth()->user()->hasRole('ADMIN'))
                            <a class="block rounded-lg {{ request()->routeIs('admin.predicate-ranges.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.predicate-ranges.index') }}">Rentang predikat</a>
                        @endif
                        <a class="block rounded-lg {{ request()->routeIs('admin.academic-years.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.academic-years.index') }}">Tahun pelajaran</a>
                        <a class="block rounded-lg {{ request()->routeIs('admin.programs.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.programs.index') }}">Program & tingkat</a>
                        <a class="block rounded-lg {{ request()->routeIs('admin.academic-classes.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.academic-classes.index') }}">Kelas / rombel</a>
                    @endauth
                    <span class="block px-3 py-2 text-slate-300">Penilaian</span>
                    <a class="block rounded-lg {{ request()->routeIs('admin.grade-entry.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.grade-entry.index') }}">Input nilai</a>
                    <span class="block px-3 py-2 text-slate-300">Rapor dan Leger</span>
                    <a class="block rounded-lg {{ request()->routeIs('admin.report-cards.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.report-cards.index') }}">Kelengkapan rapor</a>
                    <a class="block rounded-lg {{ request()->routeIs('admin.ledger.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.ledger.index') }}">Generator leger</a>
                    @if (auth()->check() && auth()->user()->hasAnyRole(['ADMIN', 'KEPSEK']))
                        <a class="block rounded-lg {{ request()->routeIs('admin.audit-logs.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.audit-logs.index') }}">Histori audit</a>
                    @endif
                    <span class="block px-3 py-2 text-slate-300">Pengaturan</span>
                    @auth
                        @if (auth()->user()->hasRole('ADMIN'))
                            <span class="block px-3 pt-5 text-xs font-semibold uppercase tracking-wider text-slate-400">Pengaturan PKBM</span>
                            <a class="block rounded-lg {{ request()->routeIs('admin.institutions.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.institutions.index') }}">Identitas PKBM</a>
                            @if (auth()->user()->institution_id)
                                <a class="block rounded-lg {{ request()->routeIs('admin.signatory-settings.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.signatory-settings.edit') }}">Penandatangan</a>
                                <a class="block rounded-lg {{ request()->routeIs('admin.settings.format.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.settings.format.edit') }}">Format rapor</a>
                                <a class="block rounded-lg {{ request()->routeIs('admin.backups.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.backups.index') }}">Backup database</a>
                            @endif
                        @endif
                    @endauth
                    @auth
                        <span class="block px-3 pt-5 text-xs font-semibold uppercase tracking-wider text-slate-400">Akun</span>
                        <a class="block rounded-lg px-3 py-2 text-slate-300 hover:bg-white/5" href="{{ route('profile.edit') }}">Profil saya</a>
                        <a class="block rounded-lg px-3 py-2 text-slate-300 hover:bg-white/5" href="{{ route('password.edit') }}">Ubah password</a>
                    @endauth
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
                <p class="mt-1 text-sm text-slate-300">Administrasi akademik PKBM</p>
            </div>
            <nav class="flex-1 space-y-1 px-4 py-5 text-sm">
                <a class="block rounded-lg {{ request()->routeIs('dashboard*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard*')) aria-current="page" @endif>Dashboard</a>
                <span class="block px-3 pt-5 text-xs font-semibold uppercase tracking-wider text-slate-400">Fondasi data inti</span>
                <span class="block px-3 py-2 text-slate-300">Master Data</span>
                @auth
                    @if (auth()->user()->hasRole('ADMIN'))
                        <a class="block rounded-lg {{ request()->routeIs('admin.teachers.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.teachers.index') }}">Data Guru</a>
                    @endif
                    <a class="block rounded-lg {{ request()->routeIs('admin.students.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.students.index') }}">Peserta didik</a>
                    <a class="block rounded-lg {{ request()->routeIs('admin.subjects.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.subjects.index') }}">Mata pelajaran</a>
                    <a class="block rounded-lg {{ request()->routeIs('admin.description-snippets.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.description-snippets.index') }}">Bank deskripsi</a>
                    @if (auth()->user()->hasRole('ADMIN'))
                        <a class="block rounded-lg {{ request()->routeIs('admin.predicate-ranges.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.predicate-ranges.index') }}">Rentang predikat</a>
                    @endif
                    <a class="block rounded-lg {{ request()->routeIs('admin.academic-years.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.academic-years.index') }}">Tahun pelajaran</a>
                    <a class="block rounded-lg {{ request()->routeIs('admin.programs.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.programs.index') }}">Program & tingkat</a>
                    <a class="block rounded-lg {{ request()->routeIs('admin.academic-classes.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.academic-classes.index') }}">Kelas / rombel</a>
                @endauth
                <span class="block px-3 py-2 text-slate-300">Penilaian</span>
                <a class="block rounded-lg {{ request()->routeIs('admin.grade-entry.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.grade-entry.index') }}">Input nilai</a>
                <span class="block px-3 py-2 text-slate-300">Rapor dan Leger</span>
                <a class="block rounded-lg {{ request()->routeIs('admin.report-cards.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.report-cards.index') }}">Kelengkapan rapor</a>
                <a class="block rounded-lg {{ request()->routeIs('admin.ledger.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.ledger.index') }}">Generator leger</a>
                @if (auth()->check() && auth()->user()->hasAnyRole(['ADMIN', 'KEPSEK']))
                    <a class="block rounded-lg {{ request()->routeIs('admin.audit-logs.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.audit-logs.index') }}">Histori audit</a>
                @endif
                <span class="block px-3 py-2 text-slate-300">Pengaturan</span>
                @auth
                    @if (auth()->user()->hasRole('ADMIN'))
                        <span class="block px-3 pt-5 text-xs font-semibold uppercase tracking-wider text-slate-400">Pengaturan PKBM</span>
                        <a class="block rounded-lg {{ request()->routeIs('admin.institutions.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.institutions.index') }}">Identitas PKBM</a>
                        @if (auth()->user()->institution_id)
                            <a class="block rounded-lg {{ request()->routeIs('admin.signatory-settings.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.signatory-settings.edit') }}">Penandatangan</a>
                            <a class="block rounded-lg {{ request()->routeIs('admin.settings.format.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.settings.format.edit') }}">Format rapor</a>
                            <a class="block rounded-lg {{ request()->routeIs('admin.backups.*') ? 'bg-white/10 font-medium' : 'text-slate-300 hover:bg-white/5' }} px-3 py-2" href="{{ route('admin.backups.index') }}">Backup database</a>
                        @endif
                    @endif
                @endauth
            </nav>
            <div class="border-t border-white/10 px-6 py-5 text-xs text-slate-300">
                Laravel {{ app()->version() }}
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
