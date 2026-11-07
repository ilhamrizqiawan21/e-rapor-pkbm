@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header eyebrow="Akun" title="Profil pengguna" description="Kelola informasi dan keamanan akun Anda." />

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center gap-4 bg-brand-900 px-6 py-7 text-white sm:px-8">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-xl font-semibold ring-1 ring-white/20" aria-hidden="true">{{ str(auth()->user()->name)->trim()->substr(0, 1)->upper() }}</div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-widest text-teal-200">Akun pengguna</p>
                    <h2 class="mt-1 break-words text-xl font-semibold">{{ auth()->user()->name }}</h2>
                    <p class="mt-1 text-sm text-slate-200">{{ auth()->user()->staffProfile?->title ?? 'Pengguna' }}</p>
                </div>
                <x-badge :variant="auth()->user()->status->value" dot>{{ auth()->user()->status->label() }}</x-badge>
            </div>
            <div class="p-6 sm:p-8">
            <h3 class="text-base font-semibold text-slate-950">Informasi akun</h3>
            <p class="mt-1 text-sm text-slate-500">Perbarui nama dan username untuk masuk.</p>
            <form method="POST" action="{{ route('profile.update') }}" class="mt-5 space-y-5">
                @csrf
                @method('PUT')
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="name" label="Nama" :value="old('name', auth()->user()->name)" :error="$errors->first('name')" required />
                    <x-input name="username" label="Username" :value="old('username', auth()->user()->username)" :error="$errors->first('username')" hint="Huruf, angka, titik, garis bawah, atau tanda hubung." autocomplete="username" required />
                </div>
                <div class="flex justify-end"><x-button type="submit" icon="save">Simpan profil</x-button></div>
            </form>
            <dl class="mt-8 grid gap-x-8 gap-y-6 border-t border-slate-100 pt-6 sm:grid-cols-2">
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
                    <dd class="mt-1"><x-badge :variant="auth()->user()->status->value" dot>{{ auth()->user()->status->label() }}</x-badge></dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-col gap-4 rounded-xl border border-slate-200 bg-slate-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="text-sm font-semibold text-slate-900">Keamanan akun</h3><p class="mt-1 text-sm text-slate-500">Perbarui kata sandi untuk menjaga keamanan akun.</p></div>
                <x-button href="{{ route('password.edit') }}" variant="secondary">Ubah password</x-button>
            </div>
            </div>
        </section>
    </div>
@endsection
