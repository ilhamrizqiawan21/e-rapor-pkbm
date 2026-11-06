@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Rapor dan leger</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Generator leger nilai</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">Pilih periode untuk mengunduh leger Excel sebagai output utama atau PDF landscape. Hanya rapor APPROVED dan LOCKED yang masuk ke leger resmi.</p>
            </div>
        </div>

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
                            <x-button href="{{ route('admin.ledger.all.excel', $semester) }}" size="sm">Unduh semua Excel</x-button>
                            <x-button href="{{ route('admin.ledger.all.pdf', $semester) }}" size="sm" variant="secondary">Unduh semua PDF</x-button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
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
                                        <x-table.td align="right"><div class="flex flex-wrap justify-end gap-2"><x-button href="{{ route('admin.ledger.class.excel', [$class, $semester]) }}" size="sm">Excel</x-button><x-button href="{{ route('admin.ledger.class.pdf', [$class, $semester]) }}" size="sm" variant="secondary">PDF</x-button></div></x-table.td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">Belum ada kelas yang dapat diekspor pada tahun pelajaran ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
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
