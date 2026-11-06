@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <x-page-header eyebrow="Master SDM" title="{{ $teacher->name }}" description="{{ $teacher->username }}{{ $teacher->email ? ' · '.$teacher->email : '' }}" back-href="{{ route('admin.teachers.index') }}" back-text="Kembali ke direktori Guru">
            <x-button href="{{ route('admin.teachers.edit', $teacher) }}" variant="edit" icon="edit">Edit data</x-button>
        </x-page-header>

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status akun</p><div class="mt-3"><x-badge :variant="$teacher->status->value" dot>{{ $teacher->status->label() }}</x-badge></div></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Penugasan mapel</p><p class="mt-3 text-2xl font-semibold text-slate-950">{{ $teacher->subject_assignments_count }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Wali kelas</p><p class="mt-3 text-2xl font-semibold text-slate-950">{{ $teacher->homeroom_classes_count }}</p></div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h3 class="text-lg font-semibold text-slate-950">Data Guru</h3>
            <dl class="mt-5 grid gap-5 sm:grid-cols-2"><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">NIP / NIY</dt><dd class="mt-1 text-sm text-slate-800">{{ $teacher->staffProfile?->employee_number ?? 'Belum diisi' }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jabatan</dt><dd class="mt-1 text-sm text-slate-800">{{ $teacher->staffProfile?->title ?? 'Belum diisi' }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Telepon</dt><dd class="mt-1 text-sm text-slate-800">{{ $teacher->staffProfile?->phone ?? 'Belum diisi' }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanda tangan laporan</dt><dd class="mt-1 text-sm text-slate-800">{{ $teacher->report_signatures_count }} snapshot</dd></div></dl>
        </section>

        <section class="rounded-2xl border border-rose-200 bg-rose-50 p-6">
            <h3 class="font-semibold text-rose-950">Zona akun</h3>
            <p class="mt-1 text-sm text-rose-800">Reset password memakai alur Admin yang sudah ada. Jika Guru memiliki histori penugasan, penghapusan otomatis berubah menjadi penonaktifan.</p>
            <div class="mt-4 flex flex-wrap gap-3"><x-button href="{{ route('admin.users.password.edit', $teacher) }}" variant="warning" icon="lock">Reset password</x-button><form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" onsubmit="return confirm('Lanjutkan penghapusan akun Guru ini? Jika memiliki histori penugasan, akun akan dinonaktifkan.')">@csrf @method('DELETE')<x-button type="submit" variant="danger" icon="trash">Hapus / nonaktifkan</x-button></form></div>
        </section>
    </div>
@endsection
