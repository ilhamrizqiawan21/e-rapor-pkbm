@extends('layouts.guest')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-slate-950">Buat password baru</h2>
            <p class="mt-1 text-sm text-slate-500">Gunakan minimal 8 karakter dengan huruf besar, huruf kecil, dan angka.</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-5" x-data="{ isSubmitting: false }" @submit="isSubmitting = true" :aria-busy="isSubmitting.toString()">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-input name="email" type="email" label="Email" :value="old('email', $email)" :error="$errors->first('email')" autocomplete="email" required autofocus />
            <x-input name="password" type="password" label="Password baru" :error="$errors->first('password')" autocomplete="new-password" required />
            <x-input name="password_confirmation" type="password" label="Ulangi password baru" autocomplete="new-password" required />
            <button type="submit" :disabled="isSubmitting" class="inline-flex w-full items-center justify-center rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-950 focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70">
                <span x-show="!isSubmitting">Simpan password baru</span>
                <span x-cloak x-show="isSubmitting">Memproses…</span>
            </button>
        </form>

        <a href="{{ route('login') }}" class="mt-5 block text-center text-sm font-medium text-brand-800 hover:text-brand-950">Kembali ke halaman masuk</a>
    </section>
@endsection
