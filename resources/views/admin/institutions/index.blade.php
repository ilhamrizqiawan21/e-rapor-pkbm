@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-header eyebrow="Pengaturan PKBM / identitas" title="Identitas PKBM" description="Kelola identitas yang digunakan pada rapor, leger, dan dokumen resmi.">
            @can('create', App\Models\Institution::class)
                <x-button href="{{ route('admin.institutions.create') }}">Tambah PKBM</x-button>
            @endcan
        </x-page-header>

        @if ($institution)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-start sm:p-8">
                    <div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-brand-50 ring-1 ring-brand-100">
                        @if ($institution->logo_path)
                            <img src="{{ Storage::disk('public')->url($institution->logo_path) }}" alt="Logo {{ $institution->name }}" class="h-full w-full object-contain p-3">
                        @else
                            <span class="text-3xl font-semibold text-brand-700">{{ str($institution->name)->substr(0, 1)->upper() }}</span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-semibold text-slate-950">{{ $institution->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $institution->npsn ? 'NPSN '.$institution->npsn : 'NPSN belum diisi' }}</p>
                            </div>
                            <x-badge variant="success" dot>Aktif</x-badge>
                        </div>
                        <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Alamat</dt><dd class="mt-1 text-slate-700">{{ $institution->address ?: 'Belum diisi' }}{{ $institution->postal_code ? ' · '.$institution->postal_code : '' }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kontak</dt><dd class="mt-1 text-slate-700">{{ $institution->phone ?: 'Telepon belum diisi' }}{{ $institution->email ? ' · '.$institution->email : '' }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor izin</dt><dd class="mt-1 text-slate-700">{{ $institution->permit_number ?: 'Belum diisi' }}</dd></div>
                        </dl>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:px-8">
                    <x-button href="{{ route('admin.institutions.show', $institution) }}" variant="secondary">Lihat detail</x-button>
                    @can('update', $institution)
                        <x-button href="{{ route('admin.institutions.edit', $institution) }}">Edit identitas</x-button>
                    @endcan
                </div>
            </section>
        @else
            <x-empty-state title="Identitas PKBM belum tersedia" description="Buat identitas PKBM terlebih dahulu agar dapat digunakan pada dokumen rapor dan leger.">
                @can('create', App\Models\Institution::class)
                    <x-slot:actions>
                        <x-button href="{{ route('admin.institutions.create') }}">Buat identitas PKBM</x-button>
                    </x-slot:actions>
                @endcan
            </x-empty-state>
        @endif
    </div>
@endsection
