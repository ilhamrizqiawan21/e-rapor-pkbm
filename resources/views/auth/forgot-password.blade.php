@extends('layouts.guest')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-slate-950">Lupa password</h2>
            <p class="mt-1 text-sm text-slate-500">Kami akan mengirim tautan reset ke email akun yang terdaftar.</p>
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5" x-data="{ isSubmitting: false }" @submit="isSubmitting = true" :aria-busy="isSubmitting.toString()">
            @csrf
            <x-input name="email" type="email" label="Email" placeholder="nama@pkbm.test" :value="old('email')" :error="$errors->first('email')" autocomplete="email" required autofocus />
            <button type="submit" :disabled="isSubmitting" class="inline-flex w-full items-center justify-center rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-950 focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70">
                <span x-show="!isSubmitting">Kirim tautan reset</span>
                <span x-cloak x-show="isSubmitting">Memproses…</span>
            </button>
        </form>

        <a href="{{ route('login') }}" class="mt-5 block text-center text-sm font-medium text-brand-800 hover:text-brand-950">Kembali ke halaman masuk</a>
    </section>
@endsection
