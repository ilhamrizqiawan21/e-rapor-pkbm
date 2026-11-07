@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <x-page-header eyebrow="Rapor & leger" title="Kenaikan & Kelulusan" description="Keputusan akhir tahun ditentukan oleh wali kelas pada semester Genap." />

    <form method="GET" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-end">
        <x-select name="class_id" label="Kelas dan tahun pelajaran">
            <option value="">Pilih kelas</option>
            @foreach($classes as $item)
                <option value="{{ $item->id }}" @selected($class?->id === $item->id)>{{ $item->name }} · {{ $item->academicYear->name }}</option>
            @endforeach
        </x-select>
        <x-button type="submit">Tampilkan</x-button>
    </form>

    @if(! $class)
        <x-empty-state title="Belum ada kelas asuhan" description="Menu ini tersedia untuk wali kelas yang sudah ditugaskan." />
    @elseif($activeSemester?->code?->value === 'GANJIL')
        <p class="rounded-xl border border-sky-200 bg-sky-50 p-5 text-sm text-sky-900">Keputusan kenaikan dan kelulusan diisi saat semester Genap. Tidak ada keputusan yang perlu diisi pada semester Ganjil.</p>
    @else
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5">
                <div>
                    <h2 class="font-semibold text-slate-950">{{ $class->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $class->academicYear->name }} · {{ $class->program->name }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2" aria-label="Ringkasan keputusan">
                    <x-badge variant="success">{{ $decided }} sudah diputuskan</x-badge>
                    @if ($missing > 0)<x-badge variant="warning">{{ $missing }} belum diputuskan</x-badge>@endif
                </div>
            </div>

            @if($finalized)
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-emerald-100 bg-emerald-50 px-5 py-4">
                    <div class="flex items-start gap-3">
                        <x-icon name="lock" class="mt-0.5 h-5 w-5 text-emerald-700" />
                        <div>
                            <div class="flex flex-wrap items-center gap-2"><x-badge variant="success" dot>Ditetapkan</x-badge><span class="text-sm font-semibold text-emerald-900">Keputusan akhir kelas sudah dikunci.</span></div>
                            <p class="mt-1 text-xs text-emerald-800">{{ $batch?->finalized_at?->format('d/m/Y H:i') ? 'Ditetapkan '.$batch->finalized_at->format('d/m/Y H:i') : 'Keputusan siap digunakan untuk arsip dan rapor.' }}</p>
                        </div>
                    </div>
                    @if(auth()->user()->hasRole('ADMIN') && $class->academicYear->status->value !== 'CLOSED')
                        <x-button href="#" @click.prevent="$dispatch('open-modal', 'academic-decision-reopen')" variant="danger" icon="unlock">Buka koreksi</x-button>
                    @endif
                </div>
            @elseif($editable)
                <div class="flex items-start gap-3 border-b border-amber-100 bg-amber-50 px-5 py-4">
                    <x-icon name="edit" class="mt-0.5 h-5 w-5 text-amber-700" />
                    <div><p class="text-sm font-semibold text-amber-900">Draf keputusan</p><p class="mt-1 text-xs text-amber-800">Periksa seluruh siswa, simpan draf, lalu tetapkan keputusan akhir sekali untuk mengunci kelas.</p></div>
                </div>
            @else
                <p class="border-b border-slate-100 bg-slate-50 px-5 py-3 text-sm text-slate-600">Hanya baca. Keputusan ditetapkan oleh wali kelas pada semester Genap aktif.</p>
            @endif

            <form method="POST" action="{{ route('academic-decisions.update', $class) }}">
                @csrf
                @method('PUT')
                <div class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <div class="grid gap-3 p-5 sm:grid-cols-2 sm:items-center">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $row['enrollment']->student->full_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $row['enrollment']->student->student_number }}</p>
                            </div>
                            <div>
                                @if($editable)
                                    <input type="hidden" name="rows[{{ $row['enrollment']->id }}][revision]" value="{{ $row['revision'] }}">
                                    <x-select name="rows[{{ $row['enrollment']->id }}][decision]" aria-label="Keputusan {{ $row['enrollment']->student->full_name }}">
                                        <option value="">Belum diputuskan</option>
                                        @foreach($options as $code => $label)
                                            <option value="{{ $code }}" @selected(old('rows.'.$row['enrollment']->id.'.decision', $row['decision']?->decision) === $code)>{{ $label }}</option>
                                        @endforeach
                                    </x-select>
                                @else
                                    <p class="text-sm {{ $row['decision'] ? 'font-semibold text-slate-800' : 'text-slate-500' }}">{{ $row['decision']?->label_snapshot ?? 'Belum diputuskan' }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="p-6 text-sm text-slate-500">Tidak ada siswa pada kelas ini.</p>
                    @endforelse
                </div>

                @if($editable && $rows->isNotEmpty())
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 p-5">
                        <p class="max-w-xl text-xs text-slate-500">Simpan draf dapat diperbaiki. Setelah ditetapkan, perubahan hanya dapat dibuka kembali oleh Admin dengan alasan dan audit.</p>
                        <x-button type="submit" variant="secondary" icon="save">Simpan draf</x-button>
                    </div>
                @endif
            </form>
            @if($editable && $rows->isNotEmpty())
                <div class="flex justify-end border-t border-slate-100 bg-white px-5 py-4">
                    @if($missing === 0)
                        <form method="POST" action="{{ route('academic-decisions.finalize', $class) }}" onsubmit="return confirm('Tetapkan keputusan akhir kelas ini? Setelah ditetapkan, perubahan memerlukan pembukaan koreksi oleh Admin.')">
                            @csrf
                            <x-button type="submit" variant="success" icon="lock">Tetapkan keputusan akhir</x-button>
                        </form>
                    @else
                        <x-button type="button" variant="success" icon="lock" disabled>Tetapkan ({{ $missing }} belum diputuskan)</x-button>
                    @endif
                </div>
            @endif
        </section>
    @endif
</div>

@if($class && $finalized && auth()->user()->hasRole('ADMIN') && $class->academicYear->status->value !== 'CLOSED')
    <x-modal name="academic-decision-reopen" title="Buka koreksi keputusan" max-width="2xl">
        <form method="POST" action="{{ route('admin.academic-decisions.reopen', $class) }}" class="space-y-5">
            @csrf
            <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Pembukaan koreksi dicatat dalam audit. Setelah koreksi selesai, wali kelas harus menetapkan keputusan akhir kembali.</p>
            <x-input name="reason" label="Alasan koreksi" hint="Minimal 10 karakter. Jelaskan alasan pembukaan keputusan." required />
            <div class="flex justify-end gap-2">
                <x-button type="button" variant="cancel" @click="$dispatch('close-modal', 'academic-decision-reopen')">Batal</x-button>
                <x-button type="submit" variant="danger" icon="unlock">Buka koreksi</x-button>
            </div>
        </form>
    </x-modal>
@endif
@endsection
