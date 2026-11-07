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
    $completionPercentage = $progress['total'] > 0 ? (int) round(($progress['completed'] / $progress['total']) * 100) : 0;
    $student = $reportCard->enrollment?->student;
    $academicClass = $reportCard->enrollment?->academicClass;
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

    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Kelengkapan rapor" title="Tinjau dan lengkapi rapor" description="Selesaikan komponen wajib terlebih dahulu, kemudian tambahkan informasi pendukung sebelum rapor diterbitkan." back-href="{{ route('admin.report-cards.index') }}" back-text="Kembali ke antrean rapor" />

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <div class="grid items-start gap-6 lg:grid-cols-[19rem_minmax(0,1fr)]">
            <aside class="space-y-4 lg:sticky lg:top-24" aria-label="Ringkasan dan checklist rapor">
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="bg-brand-950 px-5 py-5 text-white">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-300">Peserta didik</p>
                        <h2 class="mt-2 text-lg font-semibold">{{ $student?->full_name ?? 'Nama belum tersedia' }}</h2>
                        <p class="mt-1 text-xs text-slate-300">{{ $student?->student_number ?? 'NIPD belum diisi' }}</p>
                    </div>
                    <dl class="grid gap-4 px-5 py-4 text-sm">
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kelas</dt><dd class="mt-1 font-medium text-slate-900">{{ $academicClass?->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Periode</dt><dd class="mt-1 font-medium text-slate-900">{{ $reportCard->semester?->academicYear?->name ?? '—' }} · {{ $reportCard->semester?->name ?? '—' }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="required-components-title">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Progres wajib</p>
                            <h2 id="required-components-title" class="mt-1 text-lg font-semibold text-slate-950">{{ $progress['completed'] }}/{{ $progress['total'] }} lengkap</h2>
                        </div>
                        <span class="text-sm font-semibold {{ $progress['complete'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $completionPercentage }}%</span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200" aria-hidden="true"><div class="h-full rounded-full {{ $progress['complete'] ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $completionPercentage }}%"></div></div>

                    <ol class="mt-5 space-y-2">
                        @foreach ($progress['components'] as $component)
                            @php
                                $componentHref = match ($component['key']) {
                                    'document_number' => route('admin.report-cards.identity.edit', $reportCard),
                                    'attitude_spiritual', 'attitude_social', 'attendance' => route('admin.report-cards.assessments.show', $reportCard),
                                    'grades' => route('admin.grade-entry.index', ['class_id' => $academicClass?->id]),
                                    'academic_decision' => route('academic-decisions.index', ['class_id' => $academicClass?->id]),
                                    default => null,
                                };
                            @endphp
                            <li>
                                @if ($componentHref)
                                    <a href="{{ $componentHref }}" class="group flex items-start gap-3 rounded-xl px-2 py-2 transition-colors hover:bg-slate-50 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500">
                                @else
                                    <div class="flex items-start gap-3 rounded-xl px-2 py-2">
                                @endif
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $component['complete'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}" aria-hidden="true">{{ $component['complete'] ? '✓' : '!' }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold {{ $component['complete'] ? 'text-slate-700' : 'text-slate-950' }}">{{ $component['label'] }}</span>
                                        <span class="mt-0.5 block text-xs leading-5 {{ $component['complete'] ? 'text-emerald-700' : 'text-slate-500' }}">{{ $component['complete'] ? 'Sudah lengkap' : $component['message'] }}</span>
                                    </span>
                                    @if ($componentHref)
                                        <span class="mt-1 text-slate-300 transition-transform group-hover:translate-x-0.5 group-hover:text-brand-700" aria-hidden="true">→</span>
                                    @endif
                                @if ($componentHref)
                                    </a>
                                @else
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ol>

                    @if (! $editable)
                        <div class="mt-4 border-t border-slate-100 pt-4"><x-badge variant="LOCKED" dot>Mode hanya baca</x-badge></div>
                    @endif
                </section>

                @can('export', $reportCard)
                    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Pratinjau bagian rapor">
                        <p class="px-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Pratinjau bagian</p>
                        <div class="mt-3 grid gap-2">
                            <x-button href="{{ route('admin.report-cards.pdf.identity', ['report_card' => $reportCard, 'preview' => 1]) }}" size="sm" variant="identity" icon="pdf" target="_blank" rel="noopener">Identitas</x-button>
                            <x-button href="{{ route('admin.report-cards.pdf.grades', ['report_card' => $reportCard, 'preview' => 1]) }}" size="sm" variant="grades" icon="pdf" target="_blank" rel="noopener">Nilai</x-button>
                        </div>
                    </section>
                @endcan
            </aside>

            <main class="min-w-0 space-y-6">
                @include('admin.report-cards.partials.workflow-actions', ['reportCard' => $reportCard])

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Isi tambahan rapor</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-950">Ekstrakurikuler dan catatan wali kelas</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Bagian berikut bersifat pendukung dan tidak mengubah hitungan komponen wajib di atas.</p>
                </div>

                <section id="extracurricular" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" x-data="extracurricularEditor(@js($initialRows))">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Bagian tambahan</p>
                            <h3 class="mt-1 text-xl font-semibold text-slate-950">Ekstrakurikuler</h3>
                            <p class="mt-1 text-sm text-slate-600">Tambahkan kegiatan dan keterangan capaian sesuai urutan yang akan tampil pada rapor.</p>
                        </div>
                        <button type="button" @click="addRow()" @disabled(! $editable) class="inline-flex min-h-10 items-center justify-center rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-900 transition-colors hover:bg-brand-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 disabled:cursor-not-allowed disabled:opacity-50">+ Tambah kegiatan</button>
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
                                    <div class="grid gap-4 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)_auto] xl:items-start">
                                        <div><label class="text-sm font-semibold text-slate-800" :for="'activity-name-' + row.key">Nama kegiatan</label><input class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100" type="text" :id="'activity-name-' + row.key" :name="'rows[' + index + '][activity_name]'" x-model="row.activity_name" maxlength="120" placeholder="Contoh: Pramuka" @disabled(! $editable)></div>
                                        <div><label class="text-sm font-semibold text-slate-800" :for="'activity-description-' + row.key">Keterangan</label><textarea class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100" :id="'activity-description-' + row.key" :name="'rows[' + index + '][description]'" x-model="row.description" maxlength="500" rows="2" placeholder="Contoh: Aktif dan menunjukkan tanggung jawab." @disabled(! $editable)></textarea></div>
                                        <div class="flex flex-wrap gap-2 xl:justify-end">
                                            <button type="button" @click="moveRow(index, -1)" :disabled="index === 0 || {{ $editable ? 'false' : 'true' }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40" :aria-label="'Naikkan kegiatan baris ' + (index + 1)">Naik</button>
                                            <button type="button" @click="moveRow(index, 1)" :disabled="index === rows.length - 1 || {{ $editable ? 'false' : 'true' }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40" :aria-label="'Turunkan kegiatan baris ' + (index + 1)">Turun</button>
                                            <button type="button" @click="removeRow(index)" @disabled(! $editable) class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40">Hapus</button>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </div>
                        @if ($editable)
                            <div class="flex justify-end border-t border-slate-100 pt-4"><x-button type="submit" variant="success" icon="save">Simpan ekstrakurikuler</x-button></div>
                        @endif
                    </form>
                </section>

                <section id="homeroom-note" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Bagian tambahan</p>
                        <h3 class="mt-1 text-xl font-semibold text-slate-950">Catatan wali kelas</h3>
                        <p class="mt-1 text-sm text-slate-600">Tulis catatan atau saran yang akan tampil pada halaman rapor peserta didik.</p>
                    </div>
                    <form class="mt-5 space-y-4" method="POST" action="{{ route('admin.report-cards.completeness.homeroom-note.update', $reportCard) }}" x-data="{ note: @js($initialNote) }">
                        @csrf
                        @method('PUT')
                        <div>
                            <div class="flex items-center justify-between gap-3"><label for="homeroom-note-field" class="text-sm font-semibold text-slate-800">Catatan / saran</label><span class="text-xs font-medium text-slate-500"><span x-text="note.length">0</span>/{{ $noteMaxLength }} karakter</span></div>
                            <textarea id="homeroom-note-field" name="note" x-model="note" maxlength="{{ $noteMaxLength }}" rows="5" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Tulis catatan wali kelas..." @disabled(! $editable)></textarea>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2"><p class="text-xs text-slate-500">Maksimal {{ $noteMaxLength }} karakter. Kosongkan untuk menghapus catatan.</p><button type="button" class="text-xs font-semibold text-brand-800 underline hover:text-brand-950 disabled:text-slate-400" data-description-picker data-target="homeroom-note-field" data-picker-url="{{ route('admin.description-snippets.picker') }}" @disabled(! $editable)>Gunakan bank deskripsi</button></div>
                        </div>
                        @if ($editable)
                            <div class="flex justify-end border-t border-slate-100 pt-4"><x-button type="submit" variant="success" icon="save">Simpan catatan</x-button></div>
                        @endif
                    </form>
                </section>
            </main>
        </div>
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
