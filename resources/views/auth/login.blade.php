@extends('layouts.guest')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-slate-950">Masuk ke aplikasi</h2>
            <p class="mt-1 text-sm text-slate-500">Gunakan username atau email yang terdaftar.</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5" x-data="{ isSubmitting: false }" @submit="isSubmitting = true" :aria-busy="isSubmitting.toString()">
            @csrf

            <x-input
                name="login"
                label="Username atau email"
                placeholder="admin atau admin@erapor.test"
                :value="old('login')"
                :error="$errors->first('login')"
                autocomplete="username"
                required
                autofocus
            />

            <x-input
                name="password"
                type="password"
                label="Kata sandi"
                placeholder="Masukkan kata sandi"
                :error="$errors->first('password')"
                autocomplete="current-password"
                required
            />

            <div class="flex flex-wrap items-start justify-between gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                    Ingat saya
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-800 hover:text-brand-950">Lupa password?</a>
            </div>

            <button type="submit" :disabled="isSubmitting" class="inline-flex w-full items-center justify-center rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-950 focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70">
                <span x-show="!isSubmitting">Masuk</span>
                <span x-cloak x-show="isSubmitting" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    Memproses…
                </span>
            </button>
        </form>
    </section>
@endsection
