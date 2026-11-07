@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-header eyebrow="Administrasi lembaga" title="Pengaturan PKBM" description="Kelola identitas, dokumen rapor, dan cadangan data lembaga dalam satu tempat.">
            @if (! $institution)
            @can('create', App\Models\Institution::class)
                <x-button href="#" icon="add" @click.prevent="$dispatch('open-modal', 'institution-create')">Tambah PKBM</x-button>
            @endcan
            @endif
        </x-page-header>

        @if ($institution)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-start sm:p-8">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-brand-50 ring-1 ring-brand-100 sm:h-24 sm:w-24">
                        @if ($institution->logo_path)
                            <img src="{{ Storage::disk('public')->url($institution->logo_path) }}" alt="Logo {{ $institution->name }}" class="h-full w-full object-contain p-3">
                        @else
                            <span class="text-3xl font-semibold text-brand-700">{{ str($institution->name)->substr(0, 1)->upper() }}</span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-accent-700">Identitas lembaga</p>
                                <h3 class="mt-2 text-xl font-semibold text-slate-950">{{ $institution->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $institution->npsn ? 'NPSN '.$institution->npsn : 'NPSN belum diisi' }}</p>
                            </div>
                        </div>
                        <dl class="mt-6 grid gap-5 border-t border-slate-100 pt-5 text-sm sm:grid-cols-2">
                            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Alamat</dt><dd class="mt-1 text-slate-700">{{ $institution->address ?: 'Belum diisi' }}{{ $institution->postal_code ? ' · '.$institution->postal_code : '' }}</dd></div>
                            <div class="min-w-0"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kontak</dt><dd class="mt-1 break-words text-slate-700">{{ $institution->phone ?: 'Telepon belum diisi' }}{{ $institution->email ? ' · '.$institution->email : '' }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor izin</dt><dd class="mt-1 text-slate-700">{{ $institution->permit_number ?: 'Belum diisi' }}</dd></div>
                        </dl>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:px-8">
                    @can('update', $institution)
                        <x-button href="#" @click.prevent="$dispatch('open-modal', 'institution-edit-{{ $institution->id }}')" variant="edit" icon="edit">Edit identitas</x-button>
                    @endcan
                    <x-button href="{{ route('admin.institutions.show', $institution) }}" variant="detail" icon="eye">Lihat detail</x-button>
                </div>
            </section>
        @else
            <x-empty-state title="Identitas PKBM belum tersedia" description="Buat identitas PKBM terlebih dahulu agar dapat digunakan pada dokumen rapor dan leger.">
                @can('create', App\Models\Institution::class)
                    <x-slot:actions>
                        <x-button href="#" @click.prevent="$dispatch('open-modal', 'institution-create')" icon="add">Buat identitas PKBM</x-button>
                    </x-slot:actions>
                @endcan
            </x-empty-state>
        @endif

        @if ($institution)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="pkbm-settings-links">
                <div>
                    <h2 id="pkbm-settings-links" class="text-lg font-semibold text-slate-950">Dokumen dan data</h2>
                    <p class="mt-1 text-sm text-slate-500">Pilih bagian yang ingin dikelola.</p>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <a href="{{ route('admin.signatory-settings.edit') }}" class="group flex flex-col rounded-xl border border-slate-200 bg-slate-50/60 p-5 transition-colors hover:border-brand-200 hover:bg-brand-50 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><x-nav-icon name="signatory" /></span>
                        <span class="mt-3 block text-sm font-semibold text-slate-900 group-hover:text-brand-900">Penandatangan</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">Pilih akun kepala PKBM atau isi nama, jabatan, dan NIP/NIY secara manual.</span>
                        <span class="mt-auto block pt-5 text-xs font-semibold text-brand-700">Atur penandatangan <span aria-hidden="true">→</span></span>
                    </a>
                    <a href="{{ route('admin.settings.format.edit') }}" class="group flex flex-col rounded-xl border border-slate-200 bg-slate-50/60 p-5 transition-colors hover:border-brand-200 hover:bg-brand-50 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><x-nav-icon name="format" /></span>
                        <span class="mt-3 block text-sm font-semibold text-slate-900 group-hover:text-brand-900">Format rapor</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">Atur teks, logo, margin, tanda tangan, serta kolom nilai pada rapor.</span>
                        <span class="mt-auto block pt-5 text-xs font-semibold text-brand-700">Atur format <span aria-hidden="true">→</span></span>
                    </a>
                    <a href="{{ route('admin.backups.index') }}" class="group flex flex-col rounded-xl border border-slate-200 bg-slate-50/60 p-5 transition-colors hover:border-brand-200 hover:bg-brand-50 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><x-nav-icon name="backup" /></span>
                        <span class="mt-3 block text-sm font-semibold text-slate-900 group-hover:text-brand-900">Cadangan data</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">Buat dan unduh cadangan, atau pulihkan data dari file cadangan.</span>
                        <span class="mt-auto block pt-5 text-xs font-semibold text-brand-700">Kelola cadangan <span aria-hidden="true">→</span></span>
                    </a>
                </div>
            </section>
        @endif

        @can('create', App\Models\Institution::class)
            <x-modal name="institution-create" title="Tambah identitas PKBM" max-width="4xl">
                @include('admin.institutions.form', ['institution' => $modalInstitution, 'formMode' => 'create', 'modalName' => 'institution-create'])
            </x-modal>
        @endcan

        @if ($institution)
            @can('update', $institution)
                <x-modal name="institution-edit-{{ $institution->id }}" title="Edit identitas PKBM" max-width="4xl">
                    @include('admin.institutions.form', ['institution' => $institution, 'formMode' => 'edit', 'modalName' => 'institution-edit-'.$institution->id])
                </x-modal>
            @endcan
        @endif
    </div>
@endsection
