@extends('layouts.app')

@php
    $filters = $filters ?? [];
    $gradeErrors = session('grade_errors', []);
    $hasAssignment = $assignment !== null;
    $hasFilterOptions = $academicYears->isNotEmpty() && $semesters->isNotEmpty() && $academicClasses->isNotEmpty() && $subjects->isNotEmpty();
    $filterParams = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->all();
    $firstGradeWithMaterial = $students?->getCollection()
        ->map(fn ($enrollment) => $enrollment->reportCards->first()?->grades->first())
        ->first(fn ($grade) => filled($grade?->descriptionSnippet?->material));
    $materialSource = $materialContext?->descriptionSnippet ?? $firstGradeWithMaterial?->descriptionSnippet;
    $sharedMaterial = $materialContext ? $materialContext->material : $firstGradeWithMaterial?->descriptionSnippet?->material;
    $sharedDescriptionSource = $materialSource ? [
        'id' => $materialSource->id,
        'material' => $materialSource->material,
        'templates' => $materialSource->templates
            ->where('active', true)
            ->mapWithKeys(fn ($template) => [$template->predicate_code => $template->template])
            ->all(),
    ] : null;
@endphp

@section('content')
    <div class="mx-auto max-w-[1500px] space-y-6" x-data="{ dirty: false }" @input="dirty = true" @grade-autosave-state.window="dirty = $event.detail.dirty" @beforeunload.window="if (dirty) $event.returnValue = 'Perubahan nilai belum disimpan.'" data-context-save-url="{{ route('admin.grade-entry.store-context') }}">
        <x-page-header eyebrow="Penilaian" title="Input nilai" description="Pilih kelas dan mata pelajaran, lalu isi nilai peserta didik." />

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-5"><h2 class="text-base font-semibold text-slate-950">Pilih penilaian</h2><p class="mt-1 text-sm text-slate-500">Tentukan periode, kelas, dan mata pelajaran yang akan diisi.</p></div>
            <form method="GET" action="{{ route('admin.grade-entry.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5 xl:items-end">
                <x-select name="academic_year_id" label="Tahun pelajaran" :selected="$academicYear?->id" :disabled="$academicYears->isEmpty()" required>
                    @foreach ($academicYears as $year)<option value="{{ $year->id }}" @selected($academicYear?->id === $year->id)>{{ $year->name }} · {{ $year->status->label() }}</option>@endforeach
                </x-select>
                <x-select name="semester_id" label="Semester" :selected="$semester?->id" :disabled="$semesters->isEmpty()" required>
                    @foreach ($semesters as $item)<option value="{{ $item->id }}" @selected($semester?->id === $item->id)>{{ $item->name }} · {{ $item->status->label() }}</option>@endforeach
                </x-select>
                <x-select name="class_id" label="Kelas" :selected="$academicClass?->id" :disabled="$academicClasses->isEmpty()" required>
                    @foreach ($academicClasses as $class)<option value="{{ $class->id }}" @selected($academicClass?->id === $class->id)>{{ $class->name }}</option>@endforeach
                </x-select>
                <x-select name="subject_id" label="Mata pelajaran" :selected="$assignment?->subject_id" :disabled="$subjects->isEmpty()" required>
                    @foreach ($subjects as $subject)<option value="{{ $subject->id }}" @selected($assignment?->subject_id === $subject->id)>{{ $subject->name }}</option>@endforeach
                </x-select>
                <x-input name="search" label="Cari siswa" :value="$filters['search'] ?? ''" placeholder="Nama, NIPD, atau NISN" />
                <div class="flex flex-wrap gap-2 xl:col-span-5"><x-button type="submit" :disabled="! $hasFilterOptions" icon="filter">Tampilkan nilai</x-button><x-button href="{{ route('admin.grade-entry.index') }}" variant="ghost" icon="x">Reset</x-button></div>
            </form>
            @if (! $hasFilterOptions)<p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">Pilihan belum tersedia. Admin perlu menyiapkan periode, kelas, dan mata pelajaran terlebih dahulu.</p>@endif
        </section>

        @if (! $hasAssignment)
            <x-empty-state title="Penugasan nilai belum tersedia" description="Pilih konteks yang memiliki penugasan mata pelajaran aktif. Admin perlu memetakan mapel dan Guru terlebih dahulu.">
                <x-slot:actions>
                    @if (auth()->user()->hasRole('ADMIN'))
                        <x-button href="{{ route('admin.academic-classes.index') }}" variant="primary" size="sm" icon="audit">Atur kelas dan mapel</x-button>
                    @else
                        <x-button href="{{ route('dashboard') }}" variant="secondary" size="sm" icon="arrow-left">Kembali ke dashboard</x-button>
                    @endif
                </x-slot:actions>
            </x-empty-state>
        @else
            <form id="grade-entry-form" method="POST" action="{{ route('admin.grade-entry.store-all') }}" class="space-y-4" @submit.prevent>
                @csrf
                @foreach (['academic_year_id' => $academicYear->id, 'semester_id' => $semester->id, 'class_id' => $academicClass->id, 'subject_id' => $assignment->subject_id, 'subject_assignment_id' => $assignment->id] as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div><p class="text-xs font-semibold uppercase tracking-widest text-accent-700">Penilaian terpilih</p><h2 class="mt-1 text-lg font-semibold text-slate-950">{{ $assignment->subject->name }}</h2><p class="mt-1 text-sm text-slate-600">{{ $academicClass->name }} · KKM {{ $assignment->minimum_score }} · {{ $assignment->teacher?->name ?? 'Guru belum ditentukan' }}</p></div>
                        @if (auth()->user()->hasAnyRole(['ADMIN', 'GURU']))
                            <div class="flex flex-wrap justify-end gap-2">
                                <x-button :href="route('admin.grade-entry.import.create', $filterParams + ['academic_year_id' => $academicYear->id, 'semester_id' => $semester->id, 'class_id' => $academicClass->id, 'subject_id' => $assignment->subject_id, 'subject_assignment_id' => $assignment->id])" variant="excel" icon="excel">Impor dari Excel</x-button>
                                <span data-autosave-status data-autosave-state="saved" role="status" aria-label="Tersimpan otomatis" title="Tersimpan otomatis" class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                    <svg data-autosave-icon class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 5v4h4M4 13a8.1 8.1 0 0 0 15.5 2M20 19v-4h-4" /></svg>
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="mt-5 rounded-xl border border-accent-100 bg-accent-50/60 p-4 sm:p-5">
                        <label for="shared-material" class="block text-sm font-semibold text-slate-900">Materi / capaian pembelajaran</label>
                        <p class="mt-1 text-xs leading-5 text-slate-600">Isi satu kali. Materi ini digunakan untuk seluruh peserta didik pada mata pelajaran ini; sistem menyesuaikan kalimat berdasarkan predikat masing-masing.</p>
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                            <input id="shared-material" type="text" name="material" value="{{ old('material', $sharedMaterial) }}" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20" placeholder="Contoh: memahami pengertian sifat terpuji" data-shared-material>
                            <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-brand-200 bg-white px-3 py-2 text-sm font-semibold text-brand-800 hover:bg-brand-50" data-description-picker data-material-target="shared-material" data-picker-url="{{ route('admin.description-snippets.picker', ['subject_id' => $assignment->subject_id]) }}">Pilih dari bank deskripsi</button>
                        </div>
                    </div>
                </div>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4"><div><h2 class="text-base font-semibold text-slate-950">Daftar nilai</h2><p class="mt-1 text-xs text-slate-500">Isi nilai 0–100. Predikat dan deskripsi diperbarui otomatis.</p></div><span class="text-xs text-slate-500">{{ $students->total() }} peserta didik</span></div>
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-600 md:hidden">Geser tabel ke kanan untuk melihat kolom deskripsi dan aksi.</div>
                    <div class="overflow-x-auto" role="region" tabindex="0" aria-label="Tabel input nilai peserta didik"><table class="min-w-[1100px] divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50"><tr><x-table.th>No.</x-table.th><x-table.th>Peserta didik</x-table.th><x-table.th width="130">Nilai (0–100)</x-table.th><x-table.th width="120">Predikat</x-table.th><x-table.th>Deskripsi capaian</x-table.th><x-table.th>Status</x-table.th><x-table.th align="right">Aksi</x-table.th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($students as $index => $enrollment)
                                @php($reportCard = $enrollment->reportCards->first())
                                @php($grade = $reportCard?->grades->first())
                                @php($rowId = $enrollment->id)
                                @php($rowError = $gradeErrors[$rowId] ?? null)
                                <tr class="align-top hover:bg-slate-50" data-grade-row="{{ $rowId }}">
                                    <x-table.td>{{ $students->firstItem() + $index }}</x-table.td>
                                    <x-table.td><p class="font-semibold text-slate-900">{{ $enrollment->student->full_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $enrollment->student->student_number }}{{ $enrollment->student->national_student_number ? ' · NISN '.$enrollment->student->national_student_number : '' }}</p></x-table.td>
                                    <x-table.td><input class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20" type="number" min="0" max="100" step="1" name="rows[{{ $rowId }}][score]" value="{{ old('rows.'.$rowId.'.score', $grade?->score) }}" aria-label="Nilai {{ $enrollment->student->full_name }}" data-score-input="{{ $rowId }}"><input type="hidden" name="rows[{{ $rowId }}][enrollment_id]" value="{{ $rowId }}"></x-table.td>
                                    <x-table.td><span id="predicate-{{ $rowId }}" class="inline-flex rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700 ring-1 ring-slate-600/10" data-predicate-badge="{{ $rowId }}" aria-label="Predikat {{ $grade?->predicate ?? 'belum ditentukan' }}">{{ $grade?->predicate ?? '—' }}</span></x-table.td>
                                    <x-table.td><div class="min-w-[330px]"><label for="description-{{ $rowId }}" class="mb-1 block text-xs font-semibold text-slate-700">Deskripsi otomatis <span class="font-normal text-slate-500">(dikunci sistem)</span></label><textarea id="description-{{ $rowId }}" name="rows[{{ $rowId }}][description]" rows="3" readonly title="Deskripsi otomatis tidak dapat diubah manual" class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20" placeholder="Isi materi di atas untuk membuat deskripsi..." data-description-field="{{ $rowId }}">{{ old('rows.'.$rowId.'.description', $grade?->description) }}</textarea><input id="description-{{ $rowId }}-snippet" type="hidden" name="rows[{{ $rowId }}][description_snippet_id]" value="{{ $grade?->description_snippet_id }}"></div></x-table.td>
                                    <x-table.td>@if ($grade)<input type="hidden" name="rows[{{ $rowId }}][expected_updated_at]" value="{{ $grade->updated_at?->toIso8601String() }}">@endif @if ($reportCard?->status === 'LOCKED')<span data-grade-status="{{ $rowId }}" data-grade-locked="true"><x-badge variant="LOCKED" dot>Terkunci</x-badge></span>@elseif ($grade?->score !== null)<span data-grade-status="{{ $rowId }}"><x-badge variant="ACTIVE" dot>Terisi</x-badge></span>@else<span data-grade-status="{{ $rowId }}"><span class="text-slate-500">Belum diisi</span></span>@endif @if ($rowError)<p class="mt-2 max-w-48 text-xs text-rose-600" role="alert">{{ $rowError }}</p>@endif</x-table.td>
                                    <x-table.td align="right"><div class="flex flex-wrap justify-end gap-2">@if (auth()->user()->hasAnyRole(['ADMIN', 'GURU']))<button type="button" data-grade-reset="{{ $rowId }}" aria-label="Kosongkan nilai {{ $enrollment->student->full_name }}" title="Kosongkan nilai" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"><svg data-reset-icon class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 5v4h4M4 13a8.1 8.1 0 0 0 15.5 2M20 19v-4h-4" /></svg></button>@else<span class="text-slate-500">Hanya baca</span>@endif</div></x-table.td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Belum ada peserta didik aktif pada kelas ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                    @if ($students)<div class="border-t border-slate-100 px-5 py-3"><x-pagination :paginator="$students" /></div>@endif
                </section>
            </form>
        @endif
    </div>

    <script>
        const predicateRanges = @js($predicateRanges);
        let descriptionSource = @js($sharedDescriptionSource);
        const sharedMaterial = document.querySelector('[data-shared-material]');

        function resolvePredicate(score) {
            if (score === '' || score === null || Number.isNaN(Number(score))) return null;
            const numericScore = Number(score);
            return predicateRanges.find((range) => numericScore >= Number(range.minimum_score) && numericScore <= Number(range.maximum_score)) || null;
        }

        function renderDescription(source, predicate, material) {
            if (!predicate || !material?.trim()) return '';
            const template = source?.templates?.[predicate.code] || `Siswa ${predicate.label.toLowerCase()} dalam {material}.`;
            return template.replaceAll('{material}', material.trim());
        }

        function predicateBadgeClasses(predicate) {
            const classes = {
                A: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/15',
                B: 'bg-sky-50 text-sky-700 ring-1 ring-sky-600/15',
                C: 'bg-amber-50 text-amber-800 ring-1 ring-amber-600/20',
                D: 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/15',
            };

            return classes[predicate?.code] || 'bg-slate-100 text-slate-700 ring-1 ring-slate-600/10';
        }

        function updateGradeRow(rowId) {
            const score = document.querySelector(`[data-score-input="${rowId}"]`);
            const badge = document.querySelector(`[data-predicate-badge="${rowId}"]`);
            const description = document.querySelector(`[data-description-field="${rowId}"]`);
            const status = document.querySelector(`[data-grade-status="${rowId}"]`);
            const predicate = resolvePredicate(score?.value ?? '');
            if (badge) {
                badge.textContent = predicate ? `${predicate.code} · ${predicate.label}` : '—';
                badge.className = `inline-flex rounded-full px-3 py-1 font-semibold ${predicateBadgeClasses(predicate)}`;
                badge.setAttribute('aria-label', predicate ? `Predikat ${predicate.code} ${predicate.label}` : 'Predikat belum ditentukan');
            }
            if (description) description.value = renderDescription(descriptionSource, predicate, sharedMaterial?.value || '');
            if (!status || status.dataset.gradeLocked === 'true') return;
            const statusBadge = status.firstElementChild;
            const isFilled = score?.value !== '' && score?.value !== null;
            if (!statusBadge) return;
            if (isFilled) {
                statusBadge.className = 'inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700';
                statusBadge.innerHTML = '<span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Terisi';
            } else {
                statusBadge.className = 'text-slate-500';
                statusBadge.textContent = 'Belum diisi';
            }
        }

        function updateAllGradeRows() {
            document.querySelectorAll('[data-score-input]').forEach((input) => updateGradeRow(input.dataset.scoreInput));
        }

        const gradeForm = document.getElementById('grade-entry-form');
        const autosaveStatus = document.querySelector('[data-autosave-status]');
        const contextSaveUrl = document.querySelector('[data-context-save-url]')?.dataset.contextSaveUrl;
        const pendingRowIds = new Set();
        let saveTimer = null;
        let contextSaveTimer = null;
        let saveController = null;
        let contextSaveController = null;

        function setStatusElement(element, state) {
            if (!element) return;
            const label = state === 'saving' ? 'Menyimpan nilai' : state === 'error' ? 'Gagal menyimpan nilai' : 'Tersimpan otomatis';
            const icon = element.querySelector('[data-autosave-icon]');
            const isHeader = element.hasAttribute('data-autosave-status');
            element.dataset.autosaveState = state;
            element.setAttribute('aria-label', label);
            element.setAttribute('title', label);
            element.className = `${isHeader ? 'inline-flex h-10 w-10' : 'inline-flex h-8 w-8'} items-center justify-center rounded-lg ${state === 'saving' ? 'bg-amber-50 text-amber-700' : state === 'error' ? 'bg-rose-50 text-rose-700' : isHeader ? 'bg-emerald-50 text-emerald-700' : 'text-slate-500'}`;
            icon?.classList.toggle('animate-spin', state === 'saving');
        }

        function setAutosaveStatus(state) {
            setStatusElement(autosaveStatus, state);
        }

        function setResetButtons(state, rowIds = pendingRowIds) {
            rowIds.forEach((rowId) => {
                const button = document.querySelector(`[data-grade-reset="${rowId}"]`);
                if (!button) return;
                const label = state === 'error' ? 'Gagal menyimpan nilai' : 'Kosongkan nilai';
                button.setAttribute('aria-label', `${label} ${document.querySelector(`[data-score-input="${rowId}"]`)?.getAttribute('aria-label')?.replace('Nilai ', '') || ''}`.trim());
                button.setAttribute('title', label);
                button.classList.toggle('bg-amber-50', state === 'saving');
                button.classList.toggle('text-amber-700', state === 'saving');
                button.classList.toggle('bg-rose-50', state === 'error');
                button.classList.toggle('text-rose-700', state === 'error');
                button.querySelector('[data-reset-icon]')?.classList.toggle('animate-spin', state === 'saving');
            });
        }

        function buildContextFormData() {
            const formData = new FormData(gradeForm);
            [...formData.keys()].forEach((key) => {
                if (key.startsWith('rows[')) formData.delete(key);
            });

            return formData;
        }

        async function saveContextAutomatically() {
            if (!gradeForm || !contextSaveUrl) return;
            const formData = buildContextFormData();
            contextSaveController?.abort();
            contextSaveController = new AbortController();
            setAutosaveStatus('saving');
            window.dispatchEvent(new CustomEvent('grade-autosave-state', { detail: { dirty: true } }));

            try {
                const response = await fetch(contextSaveUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: contextSaveController.signal,
                });
                if (!response.ok) throw new Error('Materi belum tersimpan.');
                setAutosaveStatus('saved');
                window.dispatchEvent(new CustomEvent('grade-autosave-state', { detail: { dirty: false } }));
            } catch (error) {
                if (error.name === 'AbortError') return;
                setAutosaveStatus('error');
                window.dispatchEvent(new CustomEvent('grade-autosave-state', { detail: { dirty: true } }));
            }
        }

        function queueContextSave() {
            clearTimeout(contextSaveTimer);
            setAutosaveStatus('saving');
            contextSaveTimer = setTimeout(saveContextAutomatically, 800);
        }

        async function saveGradesAutomatically() {
            if (!gradeForm) return;
            const formData = new FormData(gradeForm);
            [...formData.keys()].forEach((key) => {
                const rowMatch = key.match(/^rows\[(\d+)\]/);
                if (rowMatch && !pendingRowIds.has(rowMatch[1])) formData.delete(key);
                if (key.endsWith('[expected_updated_at]')) formData.delete(key);
            });
            const rowsBeingSaved = new Set(pendingRowIds);
            if (rowsBeingSaved.size === 0) return;
            saveController?.abort();
            saveController = new AbortController();
            setAutosaveStatus('saving');
            setResetButtons('saving', rowsBeingSaved);
            window.dispatchEvent(new CustomEvent('grade-autosave-state', { detail: { dirty: true } }));

            try {
                const response = await fetch(gradeForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: saveController.signal,
                });
                const result = await response.json();
                if (!response.ok || (result.errors && Object.keys(result.errors).length > 0)) {
                    throw new Error('Nilai belum tersimpan.');
                }
                rowsBeingSaved.forEach((rowId) => pendingRowIds.delete(rowId));
                setAutosaveStatus('saved');
                setResetButtons('saved', rowsBeingSaved);
                window.dispatchEvent(new CustomEvent('grade-autosave-state', { detail: { dirty: false } }));
            } catch (error) {
                if (error.name === 'AbortError') return;
                setAutosaveStatus('error');
                setResetButtons('error', rowsBeingSaved);
                window.dispatchEvent(new CustomEvent('grade-autosave-state', { detail: { dirty: true } }));
            }
        }

        function queueAutomaticSave() {
            clearTimeout(saveTimer);
            setAutosaveStatus('saving');
            setResetButtons('saving');
            saveTimer = setTimeout(saveGradesAutomatically, 800);
        }

        document.querySelectorAll('[data-score-input]').forEach((input) => {
            input.addEventListener('input', () => {
                updateGradeRow(input.dataset.scoreInput);
                pendingRowIds.add(input.dataset.scoreInput);
                queueAutomaticSave();
            });
            updateGradeRow(input.dataset.scoreInput);
        });

        document.querySelectorAll('[data-grade-reset]').forEach((button) => {
            button.addEventListener('click', () => {
                const rowId = button.dataset.gradeReset;
                const score = document.querySelector(`[data-score-input="${rowId}"]`);
                const snippet = document.querySelector(`#description-${rowId}-snippet`);
                if (!score || score.disabled) return;
                score.value = '';
                if (snippet) snippet.value = '';
                updateGradeRow(rowId);
                pendingRowIds.add(rowId);
                queueAutomaticSave();
            });
        });

        sharedMaterial?.addEventListener('input', () => {
            descriptionSource = { templates: {} };
            document.querySelectorAll('[id$="-snippet"]').forEach((snippet) => { snippet.value = ''; });
            updateAllGradeRows();
            queueContextSave();
        });

        document.querySelectorAll('[data-description-picker]').forEach((button) => {
            button.addEventListener('click', () => {
                const url = button.dataset.pickerUrl + '&format=json';
                window.open(url, 'description-picker-shared-material', 'width=980,height=760,resizable=yes,scrollbars=yes');
                window.addEventListener('message', (event) => {
                    if (event.origin !== window.location.origin || event.data?.type !== 'description-snippet-selected') return;
                    const payload = event.data.payload;
                    const material = document.getElementById(button.dataset.materialTarget);
                    if (material && payload) {
                        material.value = payload.material || '';
                        descriptionSource = { id: payload.id, material: payload.material, templates: payload.templates || {} };
                        document.querySelectorAll('[id$="-snippet"]').forEach((snippet) => { snippet.value = payload.id || ''; });
                        updateAllGradeRows();
                        queueContextSave();
                    }
                }, { once: true });
            });
        });
    </script>
@endsection
