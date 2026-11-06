@extends('layouts.app')

@section('content')
    @if ($role === 'ADMIN')
        @include('dashboard.admin')
    @elseif ($role === 'GURU')
        @include('dashboard.guru')
    @elseif ($role === 'KEPSEK')
        @include('dashboard.kepsek')
    @else
        <div class="mx-auto max-w-3xl rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-700">E-Rapor PKBM</p>
            <h2 class="mt-2 text-2xl font-semibold">Dashboard belum tersedia</h2>
            <p class="mt-2 text-sm">{{ $message ?? 'Role operasional belum ditetapkan pada akun ini.' }}</p>
        </div>
    @endif
@endsection
