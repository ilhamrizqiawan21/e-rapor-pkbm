@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <header class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-4"><x-breadcrumb current="Import peserta didik" parent-href="{{ route('admin.students.index') }}" parent-label="Peserta didik" /></div>
                <a href="{{ route('admin.students.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-950 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 18-6-6 6-6" /></svg>
                    Kembali ke peserta didik
                </a>
                <p class="mt-5 text-xs font-bold uppercase tracking-[0.22em] text-accent-700">Peserta didik / tambah massal</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Import peserta didik</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Tambahkan banyak data sekaligus dengan template yang konsisten. Data baru akan divalidasi dulu sebelum disimpan ke master peserta didik.</p>
            </div>
            <x-button href="{{ route('admin.students.import.template') }}" variant="excel" icon="excel">Download template XLSX</x-button>
        </header>

        <nav aria-label="Tahapan import" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-6">
            <ol class="grid grid-cols-2 gap-3 sm:flex sm:min-w-[620px] sm:items-center sm:justify-between sm:gap-4">
                @foreach ([['number' => '01', 'label' => 'Siapkan template', 'state' => 'done'], ['number' => '02', 'label' => 'Upload & validasi', 'state' => 'current'], ['number' => '03', 'label' => 'Tinjau hasil', 'state' => 'upcoming'], ['number' => '04', 'label' => 'Simpan data', 'state' => 'upcoming']] as $step)
                    <li class="flex items-center gap-3 {{ $loop->last ? '' : 'sm:flex-1' }}">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $step['state'] === 'current' ? 'bg-brand-900 text-white ring-4 ring-brand-100' : ($step['state'] === 'done' ? 'bg-accent-100 text-accent-800' : 'bg-slate-100 text-slate-500') }}">{{ $step['state'] === 'done' ? '✓' : $step['number'] }}</span>
                        <span class="whitespace-nowrap text-sm font-semibold {{ $step['state'] === 'current' ? 'text-brand-950' : 'text-slate-500' }}">{{ $step['label'] }}</span>
                        @if (! $loop->last)<span class="hidden h-px flex-1 bg-slate-200 sm:block"></span>@endif
                    </li>
                @endforeach
            </ol>
        </nav>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.65fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="upload-heading">
                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-800"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 16V4m0 0L8 8m4-4 4 4M5 15v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3" /></svg></span>
                    <div><h2 id="upload-heading" class="text-lg font-semibold text-slate-950">Upload file peserta didik</h2><p class="mt-1 text-sm leading-6 text-slate-500">Pilih file yang sudah diisi. Sistem hanya akan menambahkan baris yang valid dan tidak mengganti data yang sudah ada.</p></div>
                </div>
                <form method="POST" action="{{ route('admin.students.import.preview') }}" enctype="multipart/form-data" class="mt-7 space-y-5">
                    @csrf
                    <div>
                        <label for="file" class="block text-sm font-semibold text-slate-800">File XLSX atau CSV <span class="text-rose-500">*</span></label>
                        <div class="mt-2 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-5 transition-colors hover:border-brand-400 hover:bg-brand-50/40 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/20">
                            <input id="file" name="file" type="file" accept=".xlsx,.csv,.txt" required aria-describedby="file-help" class="block w-full cursor-pointer text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-800">
                            <p id="file-help" class="mt-3 text-xs leading-5 text-slate-500">Maksimal 10 MB dan 50.000 baris. CSV tersedia sebagai fallback jika perangkat tidak mendukung XLSX.</p>
                        </div>
                        @error('file')<p class="mt-2 text-sm text-rose-600" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs leading-5 text-slate-500">Belum punya file? <a href="{{ route('admin.students.import.template') }}" class="font-semibold text-brand-700 hover:text-brand-950">Unduh template resmi</a>.</p>
                        <x-button type="submit">Validasi dan lihat preview</x-button>
                    </div>
                </form>
            </section>

            <aside class="space-y-4">
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5" aria-labelledby="rules-heading">
                    <div class="flex items-start gap-3"><svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 4.6 2.9 17a2 2 0 0 0 1.7 3h14.8a2 2 0 0 0 1.7-3L13.7 4.6a2 2 0 0 0-3.4 0Z" /></svg><div><h2 id="rules-heading" class="font-semibold text-amber-950">Yang perlu diperhatikan</h2><ul class="mt-3 space-y-2 text-sm leading-5 text-amber-900"><li>• Kolom wajib: NIPD dan Nama Lengkap.</li><li>• Satu NIPD hanya boleh dipakai satu kali.</li><li>• Baris bermasalah akan ditandai di preview.</li></ul></div></div>
                </section>
                <section class="rounded-2xl border border-slate-200 bg-slate-900 p-5 text-white" aria-labelledby="columns-heading">
                    <h2 id="columns-heading" class="font-semibold">Kolom yang tersedia</h2>
                    <p class="mt-2 text-sm leading-5 text-slate-300">NIPD, NISN, nama lengkap, jenis kelamin, tempat/tanggal lahir, alamat, nama wali, dan status.</p>
                    <p class="mt-4 text-xs leading-5 text-slate-400">Status kosong otomatis menjadi Aktif. Pilihan status: ACTIVE, INACTIVE, GRADUATED, atau TRANSFERRED. Data lama tidak ditimpa oleh proses import.</p>
                </section>
            </aside>
        </div>
    </div>
@endsection
