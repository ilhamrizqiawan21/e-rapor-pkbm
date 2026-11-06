<section
    class="space-y-3"
    data-description-snippet-picker
    role="listbox"
    aria-label="Daftar deskripsi capaian"
>
    @forelse ($snippets as $snippet)
        @php($target = $selectionTargets[$snippet->getKey()])
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-accent-300 hover:shadow-md" data-description-snippet-id="{{ $snippet->getKey() }}">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-semibold text-slate-950">{{ $snippet->title }}</h3>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">{{ $snippet->isShared() ? 'Dibagikan' : 'Pribadi' }}</span>
                    </div>
                    <p class="mt-1 text-xs font-medium text-accent-700">{{ $snippet->subject?->name ?? 'Semua mata pelajaran' }}</p>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $snippet->content }}</p>
                </div>
                <button
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-900 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                    data-description-snippet-select
                    data-selection-target="{{ json_encode($target, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                    @click="window.opener && window.opener !== window ? (window.opener.postMessage({ type: 'description-snippet-selected', payload: @js($target) }, window.location.origin), window.close()) : $dispatch('description-snippet-selected', @js($target))"
                    role="option"
                    aria-label="Gunakan {{ $snippet->title }}"
                >
                    Gunakan deskripsi
                </button>
            </div>
        </article>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <p class="font-semibold text-slate-900">Belum ada deskripsi yang cocok.</p>
            <p class="mt-1 text-sm text-slate-500">Coba ubah kata kunci atau simpan deskripsi baru dari modul bank deskripsi.</p>
        </div>
    @endforelse

    @if ($snippets->hasPages())
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-3 shadow-sm">
            <x-pagination :paginator="$snippets" />
        </div>
    @endif
</section>
