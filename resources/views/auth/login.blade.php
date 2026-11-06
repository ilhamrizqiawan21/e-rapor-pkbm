@extends('layouts.guest')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-slate-950">Masuk ke aplikasi</h2>
            <p class="mt-1 text-sm text-slate-500">Gunakan username atau email yang terdaftar.</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf

            <x-input
                name="login"
                label="Username atau email"
                placeholder="admin atau admin@erapor.test"
                :value="old('login')"
                :error="$errors->first('login')"
                required
                autofocus
            />

            <x-input
                name="password"
                type="password"
                label="Kata sandi"
                placeholder="Masukkan kata sandi"
                :error="$errors->first('password')"
                required
            />

            <div class="flex items-center justify-between gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                    Ingat saya
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-800 hover:text-brand-950">Lupa password?</a>
            </div>

            <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-950 focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                Masuk
            </button>
        </form>
    </section>
@endsection
