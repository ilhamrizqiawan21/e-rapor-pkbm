@props([
    'paginator' => null,
])

@if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator || $paginator instanceof \Illuminate\Pagination\Paginator)
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-4 py-3">
            <div class="text-xs sm:text-sm text-slate-500">
                @if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    Menampilkan <span class="font-medium text-slate-700">{{ $paginator->firstItem() ?? 0 }}</span> sampai
                    <span class="font-medium text-slate-700">{{ $paginator->lastItem() ?? 0 }}</span> dari
                    <span class="font-medium text-slate-700">{{ $paginator->total() }}</span> hasil
                @else
                    Menampilkan <span class="font-medium text-slate-700">{{ $paginator->count() }}</span> hasil
                @endif
            </div>

            <div class="flex items-center gap-1">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-400 cursor-not-allowed">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Sebelumnya</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition shadow-xs">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Sebelumnya</span>
                    </a>
                @endif

                {{-- Pagination Elements (sm screens only) --}}
                @if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="hidden sm:flex items-center gap-1">
                        @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-900 text-xs font-semibold text-white shadow-xs">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition shadow-xs">
                        <span>Berikutnya</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @else
                    <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-400 cursor-not-allowed">
                        <span>Berikutnya</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                @endif
            </div>
        </nav>
    @endif
@else
    {{-- Static / Custom Demo pagination placeholder --}}
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-4 py-3">
        <div class="text-xs sm:text-sm text-slate-500">
            Menampilkan <span class="font-medium text-slate-700">1</span> sampai <span class="font-medium text-slate-700">10</span> dari <span class="font-medium text-slate-700">48</span> hasil
        </div>
        <div class="flex items-center gap-1">
            <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-400 cursor-not-allowed">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Sebelumnya</span>
            </span>
            <div class="hidden sm:flex items-center gap-1">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-900 text-xs font-semibold text-white">1</span>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-medium text-slate-700">2</span>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-medium text-slate-700">3</span>
            </div>
            <a href="#" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition shadow-xs">
                <span>Berikutnya</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </nav>
@endif

