@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Konfigurasi rapor</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Rentang predikat</h2>
                <p class="mt-2 text-sm text-slate-600">Atur konversi nilai 0–100 untuk {{ $institution->name }}. Rentang yang sudah tersimpan tidak mengubah rapor terkunci.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-button href="{{ route('admin.predicate-ranges.preview') }}" variant="secondary">Preview nilai</x-button>
                <x-button href="{{ route('admin.predicate-ranges.create') }}">Tambah rentang</x-button>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50"><tr>
                        <x-table.th>Urutan</x-table.th>
                        <x-table.th>Predikat</x-table.th>
                        <x-table.th>Rentang nilai</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th align="right">Aksi</x-table.th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($ranges as $range)
                            <tr class="hover:bg-slate-50">
                                <x-table.td>{{ $range->display_order }}</x-table.td>
                                <x-table.td><p class="font-semibold text-slate-900">{{ $range->code }}</p><p class="mt-1 text-xs text-slate-500">{{ $range->label }}</p></x-table.td>
                                <x-table.td>{{ $range->minimum_score }}–{{ $range->maximum_score }}</x-table.td>
                                <x-table.td><x-badge :variant="$range->active ? 'ACTIVE' : 'INACTIVE'" dot>{{ $range->active ? 'Aktif' : 'Nonaktif' }}</x-badge></x-table.td>
                                <x-table.td align="right">
                                    <x-button size="sm" variant="secondary" href="{{ route('admin.predicate-ranges.edit', $range) }}">Edit</x-button>
                                    <form class="ml-1 inline-block" method="POST" action="{{ route('admin.predicate-ranges.destroy', $range) }}" onsubmit="return confirm('Hapus rentang predikat ini?');">
                                        @csrf @method('DELETE')
                                        <x-button type="submit" size="sm" variant="danger">Hapus</x-button>
                                    </form>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr><x-table.td colspan="5">Belum ada rentang predikat.</x-table.td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
