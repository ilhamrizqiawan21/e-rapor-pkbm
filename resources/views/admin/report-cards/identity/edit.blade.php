@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-header eyebrow="Identitas rapor" title="Identitas dan nomor dokumen" description="Periksa identitas peserta didik, periode belajar, dan nomor dokumen sebelum rapor diproses." back-href="{{ route('admin.report-cards.index') }}" back-text="Kembali ke daftar rapor" />

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        @if ($context['is_locked'])
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                Rapor terkunci. Identitas pada halaman ini adalah snapshot yang digunakan saat penguncian dan tidak dapat diubah.
            </div>
        @endif

        <section class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-950">Peserta didik</h3>
                <dl class="mt-5 space-y-4 text-sm">
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Nama</dt><dd class="mt-1 font-semibold text-slate-900">{{ $context['student']['name'] ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">NIPD / NISN</dt><dd class="mt-1 text-slate-800">{{ $context['student']['student_number'] ?: '—' }} · {{ $context['student']['nisn'] ?: 'NISN belum diisi' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Kelas</dt><dd class="mt-1 font-semibold text-slate-900">{{ $context['class']['name'] ?: '—' }}</dd><dd class="text-xs text-slate-500">{{ $context['class']['program'] ?: 'Program belum diisi' }}{{ $context['class']['level'] ? ' · '.$context['class']['level'] : '' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-950">Periode dan institusi</h3>
                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Semester</dt><dd class="mt-1 font-semibold text-slate-900">{{ $context['semester']['name'] ?: $context['semester']['code'] ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Tahun pelajaran</dt><dd class="mt-1 font-semibold text-slate-900">{{ $context['academic_year']['name'] ?: '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs uppercase tracking-wide text-slate-500">PKBM</dt><dd class="mt-1 font-semibold text-slate-900">{{ $context['institution']['name'] ?: '—' }}</dd><dd class="text-xs text-slate-500">{{ $context['institution']['address'] ?: 'Alamat belum diisi' }}{{ $context['institution']['npsn'] ? ' · NPSN '.$context['institution']['npsn'] : '' }}</dd></div>
                </dl>
            </div>
        </section>

        <form method="POST" action="{{ url()->current() }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            <div class="max-w-xl">
                <label for="document_number" class="block text-sm font-semibold text-slate-800">Nomor dokumen rapor</label>
                <p class="mt-1 text-xs text-slate-500">Ketik manual. Gunakan huruf, angka, spasi, titik, garis miring, garis bawah, atau tanda hubung.</p>
                <input id="document_number" name="document_number" value="{{ old('document_number', $context['document_number']) }}" @disabled($context['is_locked']) class="mt-3 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100" maxlength="100">
                @error('document_number')<p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
            </div>
            @unless ($context['is_locked'])
                <div class="mt-5 flex justify-end border-t border-slate-100 pt-5"><button type="submit" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">Simpan nomor dokumen</button></div>
            @endunless
        </form>
    </div>
@endsection
