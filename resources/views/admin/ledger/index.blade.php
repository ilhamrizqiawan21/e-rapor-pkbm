@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Rapor / leger" title="Generator leger nilai" description="Pilih periode untuk mengunduh leger Excel sebagai output utama atau PDF landscape. Hanya rapor APPROVED dan LOCKED yang masuk ke leger resmi." />

        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900" role="status">
            Nilai, KKM, predikat, deskripsi, dan status pada leger bersumber dari snapshot nilai rapor pada periode yang dipilih.
        </div>

        <div class="space-y-5">
            @forelse ($semesters as $semester)
                @php($semesterClasses = $classes->get($semester->academic_year_id, collect()))
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                    <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">{{ $semester->academicYear?->name ?? 'Tahun pelajaran' }}</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ $semester->name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $semesterClasses->count() }} kelas yang dapat Anda akses.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($semesterClasses->isNotEmpty())
                                <x-button href="{{ route('admin.ledger.all.excel', $semester) }}" size="sm" variant="excel" icon="excel">Unduh semua Excel</x-button>
                                <x-button href="{{ route('admin.ledger.all.pdf', $semester) }}" size="sm" variant="pdf" icon="pdf">Unduh semua PDF</x-button>
                            @else
                                <span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-500">Belum ada kelas untuk diekspor</span>
                            @endif
                        </div>
                    </div>
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-[720px] divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr><x-table.th>Kelas</x-table.th><x-table.th>Program</x-table.th><x-table.th>Tingkat</x-table.th><x-table.th align="right">Aksi</x-table.th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($semesterClasses as $class)
                                    <tr class="hover:bg-slate-50">
                                        <x-table.td><p class="font-semibold text-slate-900">{{ $class->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $class->code }}</p></x-table.td>
                                        <x-table.td>{{ $class->program?->name ?? '—' }}</x-table.td>
                                        <x-table.td>{{ $class->level?->name ?? '—' }}</x-table.td>
                                        <x-table.td align="right"><div class="flex flex-wrap justify-end gap-2"><x-button href="{{ route('admin.ledger.class.excel', [$class, $semester]) }}" size="sm" variant="excel" icon="excel">Excel</x-button><x-button href="{{ route('admin.ledger.class.pdf', [$class, $semester]) }}" size="sm" variant="pdf" icon="pdf">PDF</x-button></div></x-table.td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">Belum ada kelas yang dapat diekspor pada tahun pelajaran ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="space-y-3 p-4 md:hidden">
                        @forelse ($semesterClasses as $class)
                            <article class="rounded-xl border border-slate-200 p-4" data-responsive-card>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $class->name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $class->code }}</p>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $class->level?->name ?? 'Tingkat belum diisi' }}</span>
                                </div>
                                <p class="mt-3 text-sm text-slate-600">{{ $class->program?->name ?? 'Program belum diisi' }}</p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <x-button href="{{ route('admin.ledger.class.excel', [$class, $semester]) }}" size="sm" variant="excel" icon="excel">Excel</x-button>
                                    <x-button href="{{ route('admin.ledger.class.pdf', [$class, $semester]) }}" size="sm" variant="pdf" icon="pdf">PDF</x-button>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Belum ada kelas yang dapat diekspor pada tahun pelajaran ini.</div>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-xs">
                    <h3 class="text-lg font-semibold text-slate-950">Belum ada periode leger</h3>
                    <p class="mt-2 text-sm text-slate-500">Admin perlu membuat tahun pelajaran dan semester terlebih dahulu.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
