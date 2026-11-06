@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="Master data" title="Tambah identitas PKBM" description="Isi data resmi PKBM. Logo dapat ditambahkan sekarang atau nanti." back-href="{{ route('admin.institutions.index') }}" back-text="Kembali ke identitas PKBM" />
        @include('admin.institutions.form', ['institution' => $institution, 'formMode' => 'create'])
    </div>
@endsection
