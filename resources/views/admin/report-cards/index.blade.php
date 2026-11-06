@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Rapor dan kelengkapan</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Daftar rapor peserta didik</h2>
                <p class="mt-2 text-sm text-slate-600">Periksa identitas, sikap, absensi, kegiatan, catatan wali kelas, dan progress kelengkapan setiap rapor.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[980px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <x-table.th>Peserta didik</x-table.th>
                            <x-table.th>Kelas</x-table.th>
                            <x-table.th>Periode</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th>Kelengkapan</x-table.th>
                            <x-table.th align="right">Aksi</x-table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($reportCards as $reportCard)
                            @php($summary = $progress[$reportCard->getKey()])
                            <tr class="align-top hover:bg-slate-50">
                                <x-table.td>
                                    <p class="font-semibold text-slate-900">{{ $reportCard->enrollment?->student?->full_name ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $reportCard->enrollment?->student?->student_number ?? 'NIPD belum diisi' }}</p>
                                </x-table.td>
                                <x-table.td>
                                    <p class="font-medium text-slate-900">{{ $reportCard->enrollment?->academicClass?->name ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $reportCard->enrollment?->academicClass?->program?->name ?? 'Program belum diisi' }}</p>
                                </x-table.td>
                                <x-table.td>
                                    <p class="font-medium text-slate-900">{{ $reportCard->semester?->name ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $reportCard->semester?->academicYear?->name ?? 'Tahun pelajaran belum diisi' }}</p>
                                </x-table.td>
                                <x-table.td><x-badge :variant="$reportCard->status" dot>{{ str_replace('_', ' ', $reportCard->status) }}</x-badge></x-table.td>
                                <x-table.td>
                                    <p class="font-semibold {{ $summary['complete'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $summary['completed'] }}/{{ $summary['total'] }} komponen</p>
                                    @if ($summary['missing'] !== [])
                                        <p class="mt-1 max-w-64 text-xs text-slate-500">{{ collect($summary['missing'])->pluck('label')->implode(', ') }}</p>
                                    @else
                                        <p class="mt-1 text-xs text-emerald-700">Siap diperiksa</p>
                                    @endif
                                </x-table.td>
                                <x-table.td align="right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <x-button href="{{ route('admin.report-cards.identity.edit', $reportCard) }}" size="sm" variant="secondary">Identitas</x-button>
                                        <x-button href="{{ route('admin.report-cards.assessments.show', $reportCard) }}" size="sm" variant="secondary">Sikap & absensi</x-button>
                                        <x-button href="{{ route('admin.report-cards.completeness.edit', $reportCard) }}" size="sm">Kelengkapan</x-button>
                                    </div>
                                    <div class="mt-3 text-left">
                                        @include('admin.report-cards.partials.workflow-actions', ['reportCard' => $reportCard])
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada rapor pada institusi ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($reportCards->hasPages())
                <div class="border-t border-slate-100 px-5 py-3"><x-pagination :paginator="$reportCards" /></div>
            @endif
        </section>
    </div>
@endsection
