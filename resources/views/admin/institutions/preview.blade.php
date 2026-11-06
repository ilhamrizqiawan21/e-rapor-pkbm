@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl">
        <x-page-header eyebrow="Preview dokumen" title="Preview identitas rapor" description="Pratinjau kop, identitas PKBM, dan area penandatangan sebelum modul rapor digunakan." back-href="{{ route('admin.institutions.show', $institution) }}" back-text="Kembali ke detail identitas" />

        <section class="overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm" style="padding: {{ $settings->margin_top_mm }}mm {{ $settings->margin_right_mm }}mm {{ $settings->margin_bottom_mm }}mm {{ $settings->margin_left_mm }}mm">
            <header class="border-b-2 border-brand-900 pb-5 text-center">
                @if ($settings->show_logo && $institution->logo_path)
                    <img src="{{ Storage::disk('public')->url($institution->logo_path) }}" alt="Logo {{ $institution->name }}" class="mx-auto mb-3 object-contain" style="width: {{ $settings->logo_width_mm }}mm; max-height: 30mm">
                @endif
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-800">{{ $settings->header_text ?: 'PUSAT KEGIATAN BELAJAR MASYARAKAT' }}</p>
                <h3 class="mt-2 text-2xl font-bold text-slate-950">{{ $institution->name }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ $institution->address ?: 'Alamat PKBM belum diisi' }}</p>
                <p class="text-sm text-slate-600">{{ $institution->phone ?: 'Telepon belum diisi' }}{{ $institution->email ? ' · '.$institution->email : '' }}</p>
                @if ($institution->npsn || $institution->permit_number || $institution->postal_code)
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $institution->npsn ? 'NPSN '.$institution->npsn : '' }}
                        {{ $institution->permit_number ? ' · Izin '.$institution->permit_number : '' }}
                        {{ $institution->postal_code ? ' · '.$institution->postal_code : '' }}
                    </p>
                @endif
            </header>

            <div class="py-8 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-accent-700">RAPOR PESERTA DIDIK</p>
                <h4 class="mt-3 text-xl font-semibold text-slate-950">{{ $settings->student_name_format ?: '{full_name}' }}</h4>
                <p class="mt-2 text-sm text-slate-500">Nomor dokumen: {{ $settings->document_number_format ?: '{document_number}' }}</p>
                <div class="mx-auto mt-6 max-w-2xl rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                    Area isi nilai, KKM, predikat, deskripsi, sikap, dan absensi akan diisi oleh modul rapor.
                </div>
            </div>

            <footer class="grid gap-8 border-t border-slate-200 pt-6 text-center text-sm text-slate-700 sm:grid-cols-2">
                <div>
                    <p>{{ $settings->signature_left_label ?: 'Wali Kelas' }}</p>
                    <div class="h-16"></div>
                    <p class="font-semibold">{Nama Wali Kelas}</p>
                </div>
                <div>
                    <p>{{ $settings->signature_right_label ?: 'Kepala PKBM' }}</p>
                    <div class="h-16"></div>
                    @if ($signatory?->headmaster_source?->value === 'MANUAL')
                        <p class="font-semibold">{{ $signatory->headmaster_name ?: '{Nama Kepala PKBM}' }}</p>
                        <p class="text-xs text-slate-500">{{ $signatory->headmaster_employee_number ?: 'NIP/NIY belum diisi' }}</p>
                    @elseif ($signatory?->headmasterUser)
                        <p class="font-semibold">{{ $signatory->headmasterUser->name }}</p>
                        <p class="text-xs text-slate-500">{{ $signatory->headmasterUser->staffProfile?->employee_number ?: 'NIP/NIY belum diisi' }}</p>
                    @else
                        <p class="font-semibold">{Nama Kepala PKBM}</p>
                    @endif
                </div>
            </footer>

            @if ($settings->footer_text)
                <p class="mt-8 border-t border-slate-100 pt-3 text-center text-xs text-slate-500">{{ $settings->footer_text }}</p>
            @endif
        </section>
    </div>
@endsection
