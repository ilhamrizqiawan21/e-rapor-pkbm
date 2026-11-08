@php
    // The same navigation contract renders in the desktop sidebar and mobile drawer.
    $mobile = $mobile ?? false;
    $isAdmin = auth()->check() && auth()->user()->hasRole('ADMIN');
    $isGuru = auth()->check() && auth()->user()->hasRole('GURU');
    $isKepsek = auth()->check() && auth()->user()->hasRole('KEPSEK');
    $isActive = fn (array $patterns): bool => request()->routeIs(...$patterns);
    $linkBase = 'group flex min-h-10 items-center gap-3 rounded-xl text-sm transition-colors duration-150 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent-300';
    $linkIdle = 'text-slate-300 hover:bg-white/10 hover:text-white';
    $linkActive = 'bg-accent-500/15 font-semibold text-white ring-1 ring-inset ring-accent-300/20';
    $workspaceDescription = match (true) {
        $isAdmin => 'Kelola data sampai rapor siap dicetak.',
        $isKepsek => 'Pantau kelengkapan dan hasil rapor.',
        default => 'Fokus pada penilaian, kelas, dan rapor yang menjadi tanggung jawab Anda.',
    };
    $reportGroupLabel = 'Rapor & leger';
    $reportCompletenessLabel = 'Kelengkapan rapor';
    $settingsActive = $isActive([
        'admin.institutions.*',
        'admin.signatory-settings.*',
        'admin.settings.format.*',
        'admin.backups.*',
    ]);
    $accountActive = $isActive(['profile.*', 'password.*']);
@endphp

<nav id="{{ $mobile ? 'mobile-sidebar-navigation' : 'desktop-sidebar-navigation' }}" aria-label="Navigasi utama" class="sidebar-scrollbar flex min-h-0 flex-1 flex-col overflow-y-auto px-3 py-4 {{ $mobile ? 'text-sm' : '' }}">
    <div class="mb-3 px-2 {{ $mobile ? '' : 'hidden' }}" @if (! $mobile) x-show="!sidebarCollapsed" @endif>
        <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-accent-300">Ruang kerja</p>
        <p class="mt-1 text-xs leading-5 text-slate-400">{{ $workspaceDescription }}</p>
    </div>

    {{-- Dashboard stays visible as the stable home destination. --}}
    <a href="{{ route('dashboard') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Dashboard" class="{{ $linkBase }} {{ $isActive(['dashboard*']) ? $linkActive : $linkIdle }} px-3 py-2.5">
        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $isActive(['dashboard*']) ? 'bg-accent-400/15 text-accent-200' : 'bg-white/5 text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="dashboard" /></span>
        @if ($mobile)<span>Dashboard</span>@else<span x-show="!sidebarCollapsed" x-transition.opacity>Dashboard</span>@endif
    </a>

    {{-- Data foundation is a collapsible submenu because it contains the largest menu group. --}}
    <div class="mt-5" x-data>
        <button type="button" @click="toggleNavGroup('foundation')" :aria-expanded="navGroups.foundation.toString()" @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif class="flex min-h-10 w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-slate-300 transition-colors hover:bg-white/10 hover:text-white focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent-300" title="Data inti">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/5 text-accent-300"><x-nav-icon name="institution" /></span>
            @if ($mobile)<span class="flex-1 font-semibold">Data inti</span>@else<span x-show="!sidebarCollapsed" class="flex-1 font-semibold">Data inti</span>@endif
            <svg @if (! $mobile) x-show="!sidebarCollapsed" @endif :class="{ 'rotate-180': navGroups.foundation }" class="h-4 w-4 shrink-0 text-slate-500 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
        </button>
        <div x-show="navGroups.foundation" x-transition.opacity class="mt-1 space-y-1 border-l border-white/10 pl-3" @if (! $mobile) :class="{ 'ml-0 border-0 pl-0': sidebarCollapsed }" @endif>
            @auth
                @if ($isAdmin)
                    @php($active = $isActive(['admin.teachers.*']))
                    <a href="{{ route('admin.teachers.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Data Guru" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="teachers" /></span>@if ($mobile)<span>Data Guru</span>@else<span x-show="!sidebarCollapsed">Data Guru</span>@endif</a>
                @endif
                @php($active = $isActive(['admin.students.*']))
                <a href="{{ route('admin.students.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Peserta didik" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="students" /></span>@if ($mobile)<span>Peserta didik</span>@else<span x-show="!sidebarCollapsed">Peserta didik</span>@endif</a>
                @php($active = $isActive(['admin.subjects.*']))
                <a href="{{ route('admin.subjects.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Mata pelajaran" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="subjects" /></span>@if ($mobile)<span>Mata pelajaran</span>@else<span x-show="!sidebarCollapsed">Mata pelajaran</span>@endif</a>
                @if ($isAdmin || $isGuru)
                    @php($active = $isActive(['admin.description-snippets.*']))
                    <a href="{{ route('admin.description-snippets.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Bank deskripsi" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="descriptions" /></span>@if ($mobile)<span>Bank deskripsi</span>@else<span x-show="!sidebarCollapsed">Bank deskripsi</span>@endif</a>
                @endif
                @if ($isAdmin)
                    @php($active = $isActive(['admin.predicate-ranges.*']))
                    <a href="{{ route('admin.predicate-ranges.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Rentang predikat" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="predicate" /></span>@if ($mobile)<span>Rentang predikat</span>@else<span x-show="!sidebarCollapsed">Rentang predikat</span>@endif</a>
                @endif
                @php($active = $isActive(['admin.academic-years.*']))
                <a href="{{ route('admin.academic-years.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Tahun pelajaran" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="academic-year" /></span>@if ($mobile)<span>Tahun pelajaran</span>@else<span x-show="!sidebarCollapsed">Tahun pelajaran</span>@endif</a>
                @if ($isAdmin || $isKepsek)
                    @php($active = $isActive(['admin.programs.*']))
                    <a href="{{ route('admin.programs.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Program & tingkat" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="programs" /></span>@if ($mobile)<span>Program & tingkat</span>@else<span x-show="!sidebarCollapsed">Program & tingkat</span>@endif</a>
                @endif
                @php($active = $isActive(['admin.academic-classes.*']))
                <a href="{{ route('admin.academic-classes.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif @if ($active) aria-current="page" @endif title="Kelas / rombel" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="classes" /></span>@if ($mobile)<span>Kelas / rombel</span>@else<span x-show="!sidebarCollapsed">Kelas / rombel</span>@endif</a>
                @if ($isAdmin)
                    @php($active = $isActive(['admin.academic-classes.subject-assignments.bulk-create']))
                    <a href="{{ route('admin.academic-classes.subject-assignments.bulk-create') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Penugasan guru & mapel" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="subjects" /></span>@if ($mobile)<span>Penugasan guru & mapel</span>@else<span x-show="!sidebarCollapsed">Penugasan guru & mapel</span>@endif</a>
                @endif
            @endauth
        </div>
    </div>

    {{-- Assessment is intentionally a separate step from data foundation. --}}
    @if (! $isKepsek)
    <div class="mt-3">
        <button type="button" @click="toggleNavGroup('assessment')" :aria-expanded="navGroups.assessment.toString()" @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif class="flex min-h-10 w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-slate-300 transition-colors hover:bg-white/10 hover:text-white focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent-300" title="Penilaian">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/5 text-accent-300"><x-nav-icon name="grades" /></span>
            @if ($mobile)<span class="flex-1 font-semibold">Penilaian</span>@else<span x-show="!sidebarCollapsed" class="flex-1 font-semibold">Penilaian</span>@endif
            <svg @if (! $mobile) x-show="!sidebarCollapsed" @endif :class="{ 'rotate-180': navGroups.assessment }" class="h-4 w-4 shrink-0 text-slate-500 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
        </button>
        <div x-show="navGroups.assessment" x-transition.opacity class="mt-1 space-y-1 border-l border-white/10 pl-3" @if (! $mobile) :class="{ 'ml-0 border-0 pl-0': sidebarCollapsed }" @endif>
            @php($active = $isActive(['admin.grade-entry.*']))
            <a href="{{ route('admin.grade-entry.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Input nilai" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="grades" /></span>@if ($mobile)<span>Input nilai</span>@else<span x-show="!sidebarCollapsed">Input nilai</span>@endif</a>
        </div>
    </div>
    @endif

    {{-- Report and ledger work share one review-oriented group. --}}
    <div class="mt-3">
        <button type="button" @click="toggleNavGroup('reports')" :aria-expanded="navGroups.reports.toString()" @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif class="flex min-h-10 w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-slate-300 transition-colors hover:bg-white/10 hover:text-white focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent-300" title="{{ $reportGroupLabel }}">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/5 text-accent-300"><x-nav-icon name="report" /></span>
            @if ($mobile)<span class="flex-1 font-semibold">{{ $reportGroupLabel }}</span>@else<span x-show="!sidebarCollapsed" class="flex-1 font-semibold">{{ $reportGroupLabel }}</span>@endif
            <svg @if (! $mobile) x-show="!sidebarCollapsed" @endif :class="{ 'rotate-180': navGroups.reports }" class="h-4 w-4 shrink-0 text-slate-500 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
        </button>
        <div x-show="navGroups.reports" x-transition.opacity class="mt-1 space-y-1 border-l border-white/10 pl-3" @if (! $mobile) :class="{ 'ml-0 border-0 pl-0': sidebarCollapsed }" @endif>
            @if ($isKepsek && \Illuminate\Support\Facades\Route::has('admin.grade-monitoring.index'))
                @php($active = $isActive(['admin.grade-monitoring.*']))
                <a href="{{ route('admin.grade-monitoring.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Progres nilai" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="grades" /></span>@if ($mobile)<span>Progres nilai</span>@else<span x-show="!sidebarCollapsed">Progres nilai</span>@endif</a>
            @endif
            @if($isAdmin || $isKepsek || ($hasHomeroomClass ?? false))
                @php($active = $isActive(['academic-decisions.*']))
                <a href="{{ route('academic-decisions.index') }}" @if ($mobile) @click="closeMobileSidebar()" @else :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Kenaikan & Kelulusan" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><x-nav-icon name="classes" />@if($mobile)<span>Kenaikan & Kelulusan</span>@else<span x-show="!sidebarCollapsed">Kenaikan & Kelulusan</span>@endif</a>
            @endif
            @php($active = $isActive(['admin.report-cards.*']))
            <a href="{{ route('admin.report-cards.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="{{ $reportCompletenessLabel }}" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="report" /></span>@if ($mobile)<span>{{ $reportCompletenessLabel }}</span>@else<span x-show="!sidebarCollapsed">{{ $reportCompletenessLabel }}</span>@endif</a>
            @php($active = $isActive(['admin.ledger.*']))
            <a href="{{ route('admin.ledger.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Generator leger" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="ledger" /></span>@if ($mobile)<span>Generator leger</span>@else<span x-show="!sidebarCollapsed">Generator leger</span>@endif</a>
            @php($active = $isActive(['admin.buku-induk.*']))
            <a href="{{ route('admin.buku-induk.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Buku Induk" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="buku-induk" /></span>@if ($mobile)<span>Buku Induk</span>@else<span x-show="!sidebarCollapsed">Buku Induk</span>@endif</a>
            @if (auth()->check() && auth()->user()->hasAnyRole(['ADMIN', 'KEPSEK']))
                @php($active = $isActive(['admin.audit-logs.*']))
                <a href="{{ route('admin.audit-logs.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif title="Histori audit" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2"><span class="{{ $active ? 'text-accent-200' : 'text-slate-400 group-hover:text-accent-200' }}"><x-nav-icon name="audit" /></span>@if ($mobile)<span>Histori audit</span>@else<span x-show="!sidebarCollapsed">Histori audit</span>@endif</a>
            @endif
        </div>
    </div>

    {{-- Account and PKBM settings are intentionally two direct menu links. --}}
    @auth
        <div class="mt-4 space-y-1 border-t border-white/10 pt-4">
            @php($active = $accountActive)
            <a href="{{ route('profile.edit') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif @if ($active) aria-current="page" @endif title="Akun" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $active ? 'bg-accent-400/15 text-accent-200' : 'bg-white/5 text-accent-300' }}"><x-nav-icon name="profile" /></span>
                @if ($mobile)<span>Akun</span>@else<span x-show="!sidebarCollapsed">Akun</span>@endif
            </a>
            @if ($isAdmin)
                @php($active = $settingsActive)
                <a href="{{ route('admin.institutions.index') }}" @if ($mobile) @click="closeMobileSidebar()" @endif @if (! $mobile) :class="sidebarCollapsed ? 'justify-center !px-2' : 'px-3'" @endif @if ($active) aria-current="page" @endif title="Pengaturan PKBM" class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }} px-3 py-2.5">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $active ? 'bg-accent-400/15 text-accent-200' : 'bg-white/5 text-accent-300' }}"><x-nav-icon name="format" /></span>
                    @if ($mobile)<span>Pengaturan PKBM</span>@else<span x-show="!sidebarCollapsed">Pengaturan PKBM</span>@endif
                </a>
            @endif
        </div>
    @endauth

    {{-- Identity is a sidebar footer element, not a navigation menu item. --}}
    @auth
        <div class="mt-auto border-t border-white/10 pt-4">
            <div class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2.5" @if (! $mobile) :class="sidebarCollapsed ? 'justify-center px-2' : ''" @endif>
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-accent-400/15 text-xs font-bold tracking-wide text-accent-200" aria-hidden="true">{{ $currentUserInitials ?: 'ER' }}</span>
                <div class="min-w-0" @if (! $mobile) x-show="!sidebarCollapsed" @endif>
                    <p class="truncate text-xs font-semibold text-white">{{ $currentUser?->name ?? 'Pengguna' }}</p>
                    <p class="mt-0.5 truncate text-[10px] font-medium uppercase tracking-[0.12em] text-slate-400">{{ $currentRoleLabel }}</p>
                </div>
            </div>
        </div>
    @endauth
</nav>
