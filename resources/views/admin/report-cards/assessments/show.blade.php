@extends('layouts.app')

@php
    $spiritual = $attitudes->get('SPIRITUAL');
    $social = $attitudes->get('SOCIAL');
    $editable = auth()->user()->can('updateAttitude', $reportCard);
    $pickerUrl = route('admin.description-snippets.picker');
@endphp

@section('content')
    <div class="mx-auto max-w-6xl space-y-6" x-data="{ dirty: false }" @input="dirty = true" @beforeunload.window="if (dirty) $event.returnValue = 'Perubahan sikap atau absensi belum disimpan.'">
        <x-page-header eyebrow="Rapor peserta didik" title="Sikap dan absensi" description="Lengkapi catatan wali kelas untuk {{ $reportCard->enrollment->student->full_name }} pada semester {{ $reportCard->semester->name }}." back-href="{{ route('admin.report-cards.index') }}" back-text="Kembali ke daftar rapor">
            <div class="flex items-center gap-2">
                <x-badge :variant="$reportCard->status" dot>{{ str_replace('_', ' ', $reportCard->status) }}</x-badge>
                @if ($reportCard->enrollment->academicClass->homeroom_teacher_id === auth()->id())
                    <span class="rounded-full bg-accent-50 px-3 py-1 text-xs font-semibold text-accent-800">Wali kelas</span>
                @endif
            </div>
        </x-page-header>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->has('attendance'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first('attendance') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div><h3 class="text-lg font-semibold text-slate-950">Deskripsi sikap</h3><p class="mt-1 text-sm text-slate-500">Tulis manual atau gunakan deskripsi reusable dari bank.</p></div>
                    @if (! $editable)<x-badge variant="LOCKED" dot>Hanya baca</x-badge>@endif
                </div>
                <div class="mt-5 space-y-5">
                    @foreach ([['SPIRITUAL', 'Sikap spiritual', $spiritual], ['SOCIAL', 'Sikap sosial', $social]] as [$type, $label, $assessment])
                        <form method="POST" action="{{ route('admin.report-cards.assessments.attitude', $reportCard) }}" class="rounded-xl border border-slate-200 p-4" @submit="dirty = false">
                            @csrf
                            <input type="hidden" name="attitude_type" value="{{ $type }}">
                            <div class="flex items-center justify-between gap-3">
                                <label for="description-{{ strtolower($type) }}" class="font-semibold text-slate-900">{{ $label }}</label>
                                <button type="button" class="text-xs font-semibold text-brand-800 underline disabled:text-slate-400" data-description-picker data-target="description-{{ strtolower($type) }}" @disabled(! $editable)>Pilih dari bank deskripsi</button>
                            </div>
                            <textarea id="description-{{ strtolower($type) }}" name="description" rows="4" maxlength="10000" class="mt-3 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20" placeholder="Ketik deskripsi {{ strtolower($label) }}..." @disabled(! $editable)>{{ old('description', $assessment?->description) }}</textarea>
                            @error('description')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            @if ($editable)<div class="mt-3 flex justify-end"><x-button type="submit" size="sm" icon="save">Simpan {{ strtolower($label) }}</x-button></div>@endif
                        </form>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-4"><div><h3 class="text-lg font-semibold text-slate-950">Rekap absensi</h3><p class="mt-1 text-sm text-slate-500">Isi jumlah hari selama tahun pelajaran berjalan.</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Maks. total 366 hari</span></div>
                <form method="POST" action="{{ route('admin.report-cards.assessments.attendance', $reportCard) }}" class="mt-5 space-y-4" @submit="dirty = false">
                    @csrf
                    @foreach ([['sick_days', 'Sakit'], ['leave_days', 'Izin'], ['unexcused_days', 'Tanpa keterangan']] as [$name, $label])
                        <x-input :name="$name" type="number" min="0" max="366" step="1" :label="$label.' (hari)'" :value="old($name, $attendance->{$name})" :disabled="!$editable" required />
                    @endforeach
                    @if ($editable)<div class="flex justify-end pt-2"><x-button type="submit" variant="success" icon="save">Simpan absensi</x-button></div>@endif
                </form>
            </section>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-description-picker]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.target;
                window.open(@js($pickerUrl), 'description-picker-' + target, 'width=980,height=760,resizable=yes,scrollbars=yes');
                window.addEventListener('message', (event) => {
                    if (event.origin !== window.location.origin || event.data?.type !== 'description-snippet-selected') return;
                    const description = document.getElementById(target);
                    if (description && event.data.payload) { description.value = event.data.payload.content || ''; description.dispatchEvent(new Event('input', { bubbles: true })); }
                }, { once: true });
            });
        });
    </script>
@endsection
