@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">E-Rapor PKBM</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Fondasi project sudah siap</h2>
            <p class="mt-3 max-w-2xl text-slate-600">
                Laravel, Livewire, Tailwind CSS, dan struktur domain inti sudah tersedia untuk dilanjutkan ke modul master data.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Role aplikasi', 'value' => '3', 'detail' => 'Admin · Guru · Kepsek'],
                ['label' => 'Tingkat', 'value' => '1–12', 'detail' => 'Format rapor seragam'],
                ['label' => 'Skor', 'value' => '0–100', 'detail' => 'KKM dan predikat siap'],
                ['label' => 'Deployment', 'value' => '2', 'detail' => 'Online · Localhost'],
            ] as $stat)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-2xl font-semibold text-[#123B52]">{{ $stat['value'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $stat['detail'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-slate-950">Langkah berikutnya</h3>
                        <p class="mt-1 text-sm text-slate-500">Urutan fondasi yang akan dibangun.</p>
                    </div>
                    <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">0 / 6 modul</span>
                </div>
                <ol class="mt-6 space-y-4 text-sm">
                    @foreach (['Autentikasi dan role', 'Master periode dan kelas', 'Master peserta didik dan Guru', 'Master mata pelajaran dan KKM', 'Input nilai dan bank deskripsi', 'Rapor, leger, lock/unlock'] as $index => $item)
                        <li class="flex items-center gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">{{ $index + 1 }}</span>
                            <span class="text-slate-700">{{ $item }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="rounded-2xl bg-[#123B52] p-6 text-white shadow-sm">
                <p class="text-sm font-semibold text-teal-200">Status fondasi</p>
                <h3 class="mt-2 text-xl font-semibold">Siap masuk ke modul pertama</h3>
                <p class="mt-3 text-sm leading-6 text-slate-300">
                    Migration domain inti sudah disiapkan, termasuk riwayat perpindahan kelas, reusable description, dan snapshot rapor.
                </p>
                <div class="mt-6 rounded-xl border border-white/10 bg-white/5 p-4 text-xs text-slate-300">
                    Database default: SQLite untuk development lokal.
                </div>
            </section>
        </div>
    </div>
@endsection
