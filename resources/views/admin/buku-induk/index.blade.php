@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header
            eyebrow="Dokumen & Administrasi Satuan"
            title="Buku Induk Peserta Didik"
            description="Arsip resmi catatan permanen biodata dan perkembangan belajar peserta didik sesuai format standar Dinas Pendidikan."
            back-href="{{ route('dashboard') }}"
            back-text="Kembali ke Dashboard"
        >
            <div class="flex flex-wrap items-center gap-2">
                <x-button href="{{ route('admin.buku-induk.export-excel', request()->query()) }}" variant="secondary" icon="download">
                    Ekspor Register (Excel)
                </x-button>
                <x-button href="{{ route('admin.buku-induk.register-pdf', request()->query()) }}" variant="secondary" icon="printer" target="_blank">
                    Cetak Register (PDF)
                </x-button>
            </div>
        </x-page-header>

        {{-- Filter Bar --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.buku-induk.index') }}" class="grid gap-4 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <x-input
                        name="search"
                        placeholder="Cari nama, NIPD, NISN, atau NIK..."
                        :value="request('search')"
                        label="Pencarian"
                    />
                </div>
                <div>
                    <x-select
                        name="status"
                        label="Status Siswa"
                        :selected="request('status')"
                        :options="['' => 'Semua Status', 'ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'GRADUATED' => 'Lulus', 'TRANSFERRED' => 'Pindah']"
                    />
                </div>
                <div>
                    <x-select
                        name="gender"
                        label="Jenis Kelamin"
                        :selected="request('gender')"
                        :options="['' => 'Semua', 'L' => 'Laki-laki', 'P' => 'Perempuan']"
                    />
                </div>
                <div class="sm:col-span-4 flex justify-end gap-2">
                    @if (request()->hasAny(['search', 'status', 'gender']))
                        <x-button href="{{ route('admin.buku-induk.index') }}" variant="cancel" size="sm">Reset Filter</x-button>
                    @endif
                    <x-button type="submit" size="sm" icon="filter">Terapkan Filter</x-button>
                </div>
            </form>
        </section>

        {{-- Table List --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-slate-900">Daftar Buku Induk Warga Belajar</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Menampilkan {{ $students->total() }} peserta didik terdaftar.</p>
                </div>
            </div>

            @if ($students->isEmpty())
                <div class="p-12 text-center text-slate-500">
                    <p class="text-base font-semibold text-slate-700">Tidak ada data peserta didik yang cocok.</p>
                    <p class="text-sm mt-1">Coba sesuaikan kata kunci pencarian atau filter status Anda.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <x-table.th class="w-12 text-center">No</x-table.th>
                                <x-table.th>NIPD / NISN</x-table.th>
                                <x-table.th>NIK</x-table.th>
                                <x-table.th>Nama Peserta Didik</x-table.th>
                                <x-table.th>JK</x-table.th>
                                <x-table.th>Tempat, Tanggal Lahir</x-table.th>
                                <x-table.th>Penempatan Kelas Terakhir</x-table.th>
                                <x-table.th class="text-center">Status</x-table.th>
                                <x-table.th class="text-right">Aksi</x-table.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php($statusLabels = ['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'GRADUATED' => 'Lulus', 'TRANSFERRED' => 'Pindah'])
                            @foreach ($students as $index => $student)
                                @php($latestEnrollment = $student->enrollments->first())
                                <tr class="hover:bg-slate-50/75 transition-colors">
                                    <x-table.td class="text-center text-slate-400 font-mono text-xs">
                                        {{ $students->firstItem() + $index }}
                                    </x-table.td>
                                    <x-table.td>
                                        <p class="font-semibold text-slate-900 font-mono">{{ $student->student_number }}</p>
                                        <p class="text-xs text-slate-500 font-mono">{{ $student->national_student_number ? 'NISN: '.$student->national_student_number : 'NISN: —' }}</p>
                                    </x-table.td>
                                    <x-table.td class="font-mono text-xs text-slate-600">
                                        {{ $student->nik ?: '—' }}
                                    </x-table.td>
                                    <x-table.td>
                                        <a href="{{ route('admin.buku-induk.show', $student) }}" class="font-medium text-accent-700 hover:text-accent-900 hover:underline">
                                            {{ $student->full_name }}
                                        </a>
                                        @if ($student->nickname)
                                            <span class="text-xs text-slate-400">({{ $student->nickname }})</span>
                                        @endif
                                    </x-table.td>
                                    <x-table.td class="text-center">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold {{ $student->gender === 'L' ? 'bg-sky-50 text-sky-700' : ($student->gender === 'P' ? 'bg-pink-50 text-pink-700' : 'bg-slate-100 text-slate-600') }}">
                                            {{ $student->gender ?: '—' }}
                                        </span>
                                    </x-table.td>
                                    <x-table.td class="text-xs text-slate-600">
                                        <p>{{ $student->birth_place ?: '—' }}</p>
                                        <p class="text-slate-400">{{ $student->birth_date ? $student->birth_date->format('d/m/Y') : '' }}</p>
                                    </x-table.td>
                                    <x-table.td class="text-xs">
                                        @if ($latestEnrollment && $latestEnrollment->academicClass)
                                            <p class="font-medium text-slate-800">{{ $latestEnrollment->academicClass->name }}</p>
                                            <p class="text-slate-400">{{ $latestEnrollment->academicClass->program?->name }} · {{ $latestEnrollment->academicClass->level?->name }}</p>
                                        @else
                                            <span class="text-slate-400">Belum ada kelas</span>
                                        @endif
                                    </x-table.td>
                                    <x-table.td class="text-center">
                                        <x-badge :variant="$student->status" dot>
                                            {{ $statusLabels[$student->status] ?? $student->status }}
                                        </x-badge>
                                    </x-table.td>
                                    <x-table.td class="text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <x-button href="{{ route('admin.buku-induk.show', $student) }}" variant="secondary" size="sm" icon="eye">
                                                Lihat Lembar
                                            </x-button>
                                            <x-button href="{{ route('admin.buku-induk.sheet-pdf', $student) }}" variant="ghost" size="sm" icon="printer" title="Cetak Lembar Resmi" target="_blank" />
                                        </div>
                                    </x-table.td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($students->hasPages())
                    <div class="border-t border-slate-100 p-4">
                        {{ $students->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection

