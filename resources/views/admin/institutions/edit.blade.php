@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="Data utama" title="Edit identitas PKBM" description="Perubahan akan digunakan pada dokumen akademik baru yang dicetak setelah disimpan." back-href="{{ route('admin.institutions.show', $institution) }}" back-text="Kembali ke detail identitas" />
        @include('admin.institutions.form', ['institution' => $institution, 'formMode' => 'edit'])
    </div>
@endsection
