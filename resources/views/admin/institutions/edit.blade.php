@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master data</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Edit identitas PKBM</h2>
            <p class="mt-2 text-sm text-slate-600">Perubahan akan digunakan pada dokumen akademik baru yang dicetak setelah disimpan.</p>
        </div>
        @include('admin.institutions.form', ['institution' => $institution, 'formMode' => 'edit'])
    </div>
@endsection
