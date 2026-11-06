@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="Master data" title="Detail identitas PKBM" back-href="{{ route('admin.institutions.index') }}" back-text="Kembali ke identitas PKBM">
            <x-button href="{{ route('admin.institutions.preview', $institution) }}" variant="secondary" icon="eye">Preview rapor</x-button>
            @can('update', $institution)<x-button href="{{ route('admin.institutions.edit', $institution) }}" variant="edit" icon="edit">Edit identitas</x-button>@endcan
        </x-page-header>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                <div class="flex h-32 w-32 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-brand-50 ring-1 ring-brand-100">
                    @if ($institution->logo_path)
                        <img src="{{ Storage::disk('public')->url($institution->logo_path) }}" alt="Logo {{ $institution->name }}" class="h-full w-full object-contain p-3">
                    @else
                        <span class="text-4xl font-semibold text-brand-700">{{ str($institution->name)->substr(0, 1)->upper() }}</span>
                    @endif
                </div>
                <dl class="grid flex-1 gap-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama PKBM</dt><dd class="mt-1 font-medium text-slate-900">{{ $institution->name }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">NPSN</dt><dd class="mt-1 text-slate-700">{{ $institution->npsn ?: 'Belum diisi' }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor izin</dt><dd class="mt-1 text-slate-700">{{ $institution->permit_number ?: 'Belum diisi' }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode pos</dt><dd class="mt-1 text-slate-700">{{ $institution->postal_code ?: 'Belum diisi' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Alamat</dt><dd class="mt-1 whitespace-pre-line text-slate-700">{{ $institution->address ?: 'Belum diisi' }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Telepon</dt><dd class="mt-1 text-slate-700">{{ $institution->phone ?: 'Belum diisi' }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</dt><dd class="mt-1 text-slate-700">{{ $institution->email ?: 'Belum diisi' }}</dd></div>
                </dl>
            </div>
        </section>
    </div>
@endsection
