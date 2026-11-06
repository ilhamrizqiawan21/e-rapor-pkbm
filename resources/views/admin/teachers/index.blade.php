@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master SDM</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Guru</h2>
                <p class="mt-2 text-sm text-slate-600">Kelola akun Guru, data NIP/NIY, kontak, dan status akses dalam institusi Anda.</p>
            </div>
            <x-button href="{{ route('admin.teachers.create') }}">Tambah Guru</x-button>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <form method="GET" action="{{ route('admin.teachers.index') }}" class="grid gap-4 md:grid-cols-[1fr_12rem_auto_auto] md:items-end">
                <x-input name="search" label="Cari Guru" :value="request('search')" placeholder="Nama, username, email, atau NIP/NIY" />
                <x-select name="status" label="Status" :selected="request('status')" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif']" placeholder="Semua status" />
                <x-button type="submit" variant="secondary">Terapkan</x-button>
                <x-button href="{{ route('admin.teachers.index') }}" variant="ghost">Reset</x-button>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr><x-table.th>Guru</x-table.th><x-table.th>NIP / NIY</x-table.th><x-table.th>Kontak & jabatan</x-table.th><x-table.th>Riwayat</x-table.th><x-table.th>Status</x-table.th><x-table.th align="right">Aksi</x-table.th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($teachers as $teacher)
                            <tr class="align-top hover:bg-slate-50">
                                <x-table.td><a href="{{ route('admin.teachers.show', $teacher) }}" class="font-semibold text-brand-900 hover:text-brand-700">{{ $teacher->name }}</a><p class="mt-1 text-xs text-slate-500">{{ $teacher->username }}{{ $teacher->email ? ' · '.$teacher->email : '' }}</p></x-table.td>
                                <x-table.td>{{ $teacher->staffProfile?->employee_number ?? '—' }}</x-table.td>
                                <x-table.td><p>{{ $teacher->staffProfile?->phone ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $teacher->staffProfile?->title ?? 'Jabatan belum diisi' }}</p></x-table.td>
                                <x-table.td><p>{{ $teacher->subject_assignments_count }} mapel</p><p class="mt-1 text-xs text-slate-500">{{ $teacher->homeroom_classes_count }} wali kelas</p></x-table.td>
                                <x-table.td><x-badge :variant="$teacher->status->value" dot>{{ $teacher->status->label() }}</x-badge></x-table.td>
                                <x-table.td align="right"><div class="flex flex-wrap justify-end gap-2"><x-button href="{{ route('admin.teachers.show', $teacher) }}" variant="secondary" size="sm">Detail</x-button><x-button href="{{ route('admin.teachers.edit', $teacher) }}" variant="ghost" size="sm">Edit</x-button></div></x-table.td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada akun Guru yang sesuai filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6"><x-pagination :paginator="$teachers" /></div>
        </section>
    </div>
@endsection
