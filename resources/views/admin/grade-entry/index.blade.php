@extends('layouts.app')

@php
    $filters = $filters ?? [];
    $gradeErrors = session('grade_errors', []);
    $hasAssignment = $assignment !== null;
    $hasFilterOptions = $academicYears->isNotEmpty() && $semesters->isNotEmpty() && $academicClasses->isNotEmpty() && $subjects->isNotEmpty();
    $filterParams = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->all();
@endphp

@section('content')
    <div class="mx-auto max-w-[1500px] space-y-6" x-data="{ dirty: false, picker: null }" @input="dirty = true" @beforeunload.window="if (dirty) $event.returnValue = 'Perubahan nilai belum disimpan.'" @description-snippet-selected.window="if (picker) { document.getElementById(picker).value = $event.detail.content; document.getElementById(picker + '-snippet').value = $event.detail.id; dirty = true; picker = null; }">
        <x-page-header eyebrow="Penilaian / input nilai" title="Input nilai" description="Masukkan nilai per mata pelajaran. Perubahan yang belum disimpan akan ditandai sebelum Anda berpindah halaman.">
            @if ($hasAssignment)
                <div class="flex flex-wrap gap-2 text-sm">
                    <span class="rounded-full bg-accent-50 px-3 py-2 font-semibold text-accent-800">KKM {{ $assignment->minimum_score }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-2 text-slate-700">{{ $assignment->teacher?->name ?? 'Guru belum ditentukan' }}</span>
                </div>
            @endif
        </x-page-header>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <form method="GET" action="{{ route('admin.grade-entry.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6 xl:items-end">
                <x-select name="academic_year_id" label="Tahun pelajaran" :selected="$academicYear?->id" :disabled="$academicYears->isEmpty()" hint="Pilih periode aktif untuk melanjutkan." required>
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
                <div class="flex flex-wrap gap-2"><x-button type="submit" :disabled="! $hasFilterOptions">Tampilkan</x-button><x-button href="{{ route('admin.grade-entry.index') }}" variant="ghost">Reset</x-button></div>
            </form>
            @if (! $hasFilterOptions)<p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">Pilihan belum tersedia. Admin perlu menyiapkan periode, kelas, dan mata pelajaran terlebih dahulu.</p>@endif
        </section>

        @if (! $hasAssignment)
            <x-empty-state title="Penugasan nilai belum tersedia" description="Pilih konteks yang memiliki penugasan mata pelajaran aktif. Admin perlu memetakan mapel dan Guru terlebih dahulu.">
                <x-slot:actions>
                    @if (auth()->user()->hasRole('ADMIN'))
                        <x-button href="{{ route('admin.academic-classes.index') }}" variant="primary" size="sm">Atur kelas dan mapel</x-button>
                    @else
                        <x-button href="{{ route('dashboard') }}" variant="secondary" size="sm">Kembali ke dashboard</x-button>
                    @endif
                </x-slot:actions>
            </x-empty-state>
        @else
            <form id="grade-entry-form" method="POST" action="{{ route('admin.grade-entry.store-all') }}" class="space-y-4" @submit="dirty = false">
                @csrf
                @foreach (['academic_year_id' => $academicYear->id, 'semester_id' => $semester->id, 'class_id' => $academicClass->id, 'subject_id' => $assignment->subject_id, 'subject_assignment_id' => $assignment->id] as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach

                <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <div class="text-sm text-slate-600"><span class="font-semibold text-slate-900">{{ $academicClass->name }}</span> · {{ $assignment->subject->name }} · <span class="font-semibold text-accent-700">KKM {{ $assignment->minimum_score }}</span></div>
                    @if (auth()->user()->hasAnyRole(['ADMIN', 'GURU']))<x-button type="submit" variant="success" data-save-all>Simpan semua nilai</x-button>@endif
                </div>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
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
                                    <x-table.td><input class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20" type="number" min="0" max="100" step="1" name="rows[{{ $rowId }}][score]" value="{{ old('rows.'.$rowId.'.score', $grade?->score) }}" aria-label="Nilai {{ $enrollment->student->full_name }}"><input type="hidden" name="rows[{{ $rowId }}][enrollment_id]" value="{{ $rowId }}"></x-table.td>
                                    <x-table.td><span class="inline-flex rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">{{ $grade?->predicate ?? '—' }}</span></x-table.td>
                                    <x-table.td><textarea id="description-{{ $rowId }}" name="rows[{{ $rowId }}][description]" rows="3" class="min-w-[330px] rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20" placeholder="Ketik deskripsi capaian...">{{ old('rows.'.$rowId.'.description', $grade?->description) }}</textarea><input id="description-{{ $rowId }}-snippet" type="hidden" name="rows[{{ $rowId }}][description_snippet_id]" value="{{ $grade?->description_snippet_id }}"><button type="button" class="mt-2 text-xs font-semibold text-brand-800 underline hover:text-brand-950" data-description-picker data-target="description-{{ $rowId }}" data-picker-url="{{ route('admin.description-snippets.picker', ['subject_id' => $assignment->subject_id]) }}">Pilih dari bank deskripsi</button></x-table.td>
                                    <x-table.td>@if ($grade)<input type="hidden" name="rows[{{ $rowId }}][expected_updated_at]" value="{{ $grade->updated_at?->toIso8601String() }}">@endif @if ($reportCard?->status === 'LOCKED')<x-badge variant="LOCKED" dot>Terkunci</x-badge>@elseif ($reportCard?->status === 'SUBMITTED')<x-badge variant="info" dot>Diajukan</x-badge>@elseif ($reportCard?->status === 'IN_REVIEW')<x-badge variant="warning" dot>Ditinjau</x-badge>@elseif ($reportCard?->status === 'NEEDS_REVISION')<x-badge variant="danger" dot>Perlu revisi</x-badge>@elseif ($reportCard?->status === 'APPROVED')<x-badge variant="success" dot>Disetujui</x-badge>@elseif ($grade?->score !== null)<x-badge variant="ACTIVE" dot>Terisi</x-badge>@else<span class="text-slate-500">Belum diisi</span>@endif @if ($rowError)<p class="mt-2 max-w-48 text-xs text-rose-600" role="alert">{{ $rowError }}</p>@endif</x-table.td>
                                    <x-table.td align="right"><div class="flex flex-wrap justify-end gap-2">@if (auth()->user()->hasAnyRole(['ADMIN', 'GURU']))<x-button type="submit" size="sm" variant="secondary" formaction="{{ route('admin.grade-entry.store') }}" name="enrollment_id" value="{{ $rowId }}" data-save-row="{{ $rowId }}">Simpan</x-button>@if ($reportCard && auth()->user()->hasRole('GURU') && in_array($reportCard->status, ['DRAFT', 'NEEDS_REVISION'], true))<x-button type="submit" size="sm" variant="success" formaction="{{ route('admin.report-cards.submit', $reportCard) }}">Ajukan</x-button>@endif @else<span class="text-xs text-slate-500">Hanya baca</span>@endif</div></x-table.td>
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
        document.querySelectorAll('[data-description-picker]').forEach((button) => {
            button.addEventListener('click', () => {
                const url = button.dataset.pickerUrl + '&format=json';
                const target = button.dataset.target;
                window.open(url, 'description-picker-' + target, 'width=980,height=760,resizable=yes,scrollbars=yes');
                window.addEventListener('message', (event) => {
                    if (event.origin !== window.location.origin || event.data?.type !== 'description-snippet-selected') return;
                    const payload = event.data.payload;
                    const description = document.getElementById(target);
                    const snippet = document.getElementById(target + '-snippet');
                    if (description && snippet && payload) { description.value = payload.content || ''; snippet.value = payload.id || ''; description.dispatchEvent(new Event('input', { bubbles: true })); }
                }, { once: true });
            });
        });
    </script>
@endsection
