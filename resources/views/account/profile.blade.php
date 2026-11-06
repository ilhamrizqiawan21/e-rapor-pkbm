@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header eyebrow="Akun" title="Profil pengguna" description="Informasi akun yang sedang digunakan." back-href="{{ route('dashboard') }}" back-text="Kembali ke dashboard" />

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <dl class="grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ auth()->user()->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Username</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ auth()->user()->username }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ auth()->user()->email ?? 'Belum diisi' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jabatan</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ auth()->user()->staffProfile?->title ?? 'Pengguna' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role aktif</dt>
                    <dd class="mt-1 flex flex-wrap gap-2">
                        @foreach (auth()->user()->roles as $role)
                                <x-badge variant="accent">{{ $role->code->label() }}</x-badge>
                        @endforeach
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status akun</dt>
                    <dd class="mt-1"><x-badge variant="success">{{ auth()->user()->status->label() }}</x-badge></dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('password.edit') }}" class="inline-flex items-center justify-center rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-950 focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">Ubah password</a>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">Kembali ke dashboard</a>
            </div>
        </section>
    </div>
@endsection
