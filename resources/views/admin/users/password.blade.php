@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-page-header eyebrow="Admin" title="Reset password pengguna" description="Atur password baru untuk {{ $user->name }} ({{ $user->username }})." back-href="{{ route('admin.teachers.show', $user) }}" back-text="Kembali ke detail Guru" />

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <form method="POST" action="{{ route('admin.users.password.update', $user) }}" class="space-y-5" x-data="{ isSubmitting: false }" @submit="isSubmitting = true" :aria-busy="isSubmitting.toString()">
                @csrf
                @method('PUT')
                <x-input name="password" type="password" label="Password baru" :error="$errors->first('password')" autocomplete="new-password" required autofocus />
                <x-input name="password_confirmation" type="password" label="Ulangi password baru" autocomplete="new-password" required />
                <div class="flex justify-end">
                    <button type="submit" :disabled="isSubmitting" class="inline-flex items-center justify-center rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-950 focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70">
                        <span x-show="!isSubmitting">Reset password</span>
                        <span x-cloak x-show="isSubmitting">Memproses…</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
