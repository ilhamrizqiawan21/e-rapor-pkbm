@extends('layouts.app')

@php
    $editable = auth()->user()->can('manageCompleteness', $reportCard);
    $initialRows = old('rows', $extracurriculars
        ->map(fn ($activity): array => [
            'activity_name' => $activity->activity_name,
            'description' => $activity->description ?? '',
        ])
        ->values()
        ->all());
    $initialNote = old('note', $homeroomNote?->note ?? '');
@endphp

@section('content')
    <script>
        function extracurricularEditor(initialRows) {
            return {
                rows: initialRows.map((row, index) => ({ ...row, key: 'saved-' + index + '-' + Date.now() })),
                addRow() {
                    this.rows.push({ activity_name: '', description: '', key: 'new-' + Date.now() + '-' + this.rows.length });
                },
                removeRow(index) {
                    this.rows.splice(index, 1);
                },
                moveRow(index, direction) {
                    const target = index + direction;
                    if (target < 0 || target >= this.rows.length) return;
                    [this.rows[index], this.rows[target]] = [this.rows[target], this.rows[index]];
                },
            };
        }
    </script>

    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Kelengkapan rapor</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Ekstrakurikuler & catatan wali kelas</h2>
                <p class="mt-2 text-sm text-slate-600">Lengkapi bagian rapor untuk peserta didik sebelum rapor diajukan.</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-right shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Peserta didik</p>
                <p class="mt-1 font-semibold text-slate-950">{{ $reportCard->enrollment?->student?->full_name }}</p>
                <p class="text-xs text-slate-500">{{ $reportCard->enrollment?->academicClass?->name }} · {{ $reportCard->semester?->name }}</p>
            </div>
        </div>

        <div class="rounded-2xl border {{ $progress['complete'] ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} px-5 py-4" role="status">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-semibold {{ $progress['complete'] ? 'text-emerald-800' : 'text-amber-900' }}">{{ $progress['completed'] }}/{{ $progress['total'] }} komponen wajib lengkap</p>
                    @if ($progress['missing'] !== [])
                        <p class="mt-1 text-sm {{ $progress['complete'] ? 'text-emerald-700' : 'text-amber-800' }}">Belum lengkap: {{ collect($progress['missing'])->pluck('label')->implode(', ') }}</p>
                    @else
                        <p class="mt-1 text-sm text-emerald-700">Rapor siap diajukan untuk pemeriksaan.</p>
                    @endif
                </div>
                @if (! $editable)
                    <x-badge variant="LOCKED" dot>Hanya baca</x-badge>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <section
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
            x-data="extracurricularEditor(@js($initialRows))"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">10.04</p>
                    <h3 class="mt-1 text-xl font-semibold text-slate-950">Ekstrakurikuler</h3>
                    <p class="mt-1 text-sm text-slate-600">Tambahkan kegiatan dan keterangan capaian. Urutan baris akan menjadi urutan pada rapor.</p>
                </div>
                <button type="button" @click="addRow()" @disabled(! $editable) class="inline-flex items-center justify-center rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-900 transition hover:bg-brand-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 disabled:cursor-not-allowed disabled:opacity-50">
                    + Tambah kegiatan
                </button>
            </div>

            <form class="mt-5 space-y-4" method="POST" action="{{ route('admin.report-cards.completeness.extracurriculars.update', $reportCard) }}">
                @csrf
                @method('PUT')
                <div x-show="rows.length === 0" x-cloak class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                    <p class="font-semibold text-slate-900">Belum ada kegiatan ekstrakurikuler.</p>
                    <p class="mt-1 text-sm text-slate-500">Klik “Tambah kegiatan” untuk menambahkan baris pertama.</p>
                </div>

                <div x-show="rows.length > 0" class="space-y-3">
                    <template x-for="(row, index) in rows" :key="row.key">
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="grid gap-4 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)_auto] lg:items-start">
                                <div>
                                    <label class="text-sm font-semibold text-slate-800" :for="'activity-name-' + row.key">Nama kegiatan</label>
                                    <input class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100" type="text" :id="'activity-name-' + row.key" :name="'rows[' + index + '][activity_name]'" x-model="row.activity_name" maxlength="120" placeholder="Contoh: Pramuka" @disabled(! $editable)>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-slate-800" :for="'activity-description-' + row.key">Keterangan</label>
                                    <textarea class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100" :id="'activity-description-' + row.key" :name="'rows[' + index + '][description]'" x-model="row.description" maxlength="500" rows="2" placeholder="Contoh: Aktif dan menunjukkan tanggung jawab." @disabled(! $editable)></textarea>
                                </div>
                                <div class="flex flex-wrap gap-2 lg:justify-end">
                                    <button type="button" @click="moveRow(index, -1)" :disabled="index === 0 || {{ $editable ? 'false' : 'true' }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40" :aria-label="'Naikkan kegiatan baris ' + (index + 1)">Naik</button>
                                    <button type="button" @click="moveRow(index, 1)" :disabled="index === rows.length - 1 || {{ $editable ? 'false' : 'true' }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40" :aria-label="'Turunkan kegiatan baris ' + (index + 1)">Turun</button>
                                    <button type="button" @click="removeRow(index)" @disabled(! $editable) class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40">Hapus</button>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>

                @if ($editable)
                    <div class="flex justify-end border-t border-slate-100 pt-4">
                        <x-button type="submit" variant="success">Simpan ekstrakurikuler</x-button>
                    </div>
                @endif
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">10.05</p>
                <h3 class="mt-1 text-xl font-semibold text-slate-950">Catatan wali kelas</h3>
                <p class="mt-1 text-sm text-slate-600">Tulis catatan atau saran yang akan tampil pada halaman rapor peserta didik.</p>
            </div>

            <form class="mt-5 space-y-4" method="POST" action="{{ route('admin.report-cards.completeness.homeroom-note.update', $reportCard) }}" x-data="{ note: @js($initialNote) }">
                @csrf
                @method('PUT')
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <label for="homeroom-note" class="text-sm font-semibold text-slate-800">Catatan / saran</label>
                        <span class="text-xs font-medium text-slate-500"><span x-text="note.length">0</span>/{{ $noteMaxLength }} karakter</span>
                    </div>
                    <textarea id="homeroom-note" name="note" x-model="note" maxlength="{{ $noteMaxLength }}" rows="5" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Tulis catatan wali kelas..." @disabled(! $editable)></textarea>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-slate-500">Maksimal {{ $noteMaxLength }} karakter. Kosongkan untuk menghapus catatan.</p>
                        <button type="button" class="text-xs font-semibold text-brand-800 underline hover:text-brand-950 disabled:text-slate-400" data-description-picker data-target="homeroom-note" data-picker-url="{{ route('admin.description-snippets.picker') }}" @disabled(! $editable)>Gunakan bank deskripsi</button>
                    </div>
                </div>
                @if ($editable)
                    <div class="flex justify-end border-t border-slate-100 pt-4">
                        <x-button type="submit" variant="success">Simpan catatan</x-button>
                    </div>
                @endif
            </form>
        </section>
    </div>

    <script>
        document.querySelectorAll('[data-description-picker]').forEach((button) => {
            button.addEventListener('click', () => {
                const url = new URL(button.dataset.pickerUrl, window.location.origin);
                url.searchParams.set('format', 'json');
                const target = document.getElementById(button.dataset.target);
                const listener = (event) => {
                    if (event.origin !== window.location.origin || event.data?.type !== 'description-snippet-selected') return;
                    if (target && event.data.payload) {
                        target.value = event.data.payload.content || '';
                        target.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                };
                window.addEventListener('message', listener, { once: true });
                window.open(url.toString(), 'description-picker-homeroom-note', 'width=980,height=760,resizable=yes,scrollbars=yes');
            });
        });
    </script>
@endsection
