@extends('layouts.app')
@section('content')
    @php($statusLabels = ['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'GRADUATED' => 'Lulus', 'TRANSFERRED' => 'Pindah'])
    <div class="mx-auto max-w-5xl space-y-6">
        @if (session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>@endif
        <x-page-header eyebrow="Master peserta didik" title="{{ $student->full_name }}" description="{{ $student->student_number }}{{ $student->national_student_number ? ' · NISN '.$student->national_student_number : '' }}" back-href="{{ route('admin.students.index') }}" back-text="Kembali ke peserta didik">
            <div class="flex items-center gap-2">
                <x-button href="{{ route('admin.buku-induk.show', $student) }}" variant="secondary" icon="document">
                    Lembar Buku Induk
                </x-button>
                @can('update', $student)
                    <x-button href="{{ route('admin.students.edit', $student) }}" variant="edit" icon="edit">Edit data</x-button>
                @endcan
            </div>
        </x-page-header>
        <section class="grid gap-4 sm:grid-cols-3"><div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p><div class="mt-3"><x-badge :variant="$student->status" dot>{{ $statusLabels[$student->status] ?? $student->status }}</x-badge></div></div><div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Penempatan</p><p class="mt-3 text-2xl font-semibold text-slate-950">{{ $student->enrollments->count() }}</p><p class="mt-1 text-xs text-slate-500">Riwayat penempatan kelas</p></div><div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Institusi</p><p class="mt-3 text-lg font-semibold text-slate-950">{{ $student->institution->name }}</p></div></section>
        
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-lg font-semibold text-slate-950">Identitas peserta didik & Buku Induk</h3>
                <a href="{{ route('admin.buku-induk.show', $student) }}" class="text-xs font-medium text-accent-700 hover:text-accent-800 hover:underline">Lihat Buku Induk lengkap &rarr;</a>
            </div>
            <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">NIPD / nomor induk</dt><dd class="mt-1 text-sm font-mono text-slate-800">{{ $student->student_number }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">NISN</dt><dd class="mt-1 text-sm font-mono text-slate-800">{{ $student->national_student_number ?? 'Belum diisi' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">NIK</dt><dd class="mt-1 text-sm font-mono text-slate-800">{{ $student->nik ?? 'Belum diisi' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor Kartu Keluarga (KK)</dt><dd class="mt-1 text-sm font-mono text-slate-800">{{ $student->family_card_number ?? 'Belum diisi' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis kelamin</dt><dd class="mt-1 text-sm text-slate-800">{{ $student->gender === 'L' ? 'Laki-laki' : ($student->gender === 'P' ? 'Perempuan' : 'Belum diisi') }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tempat, tanggal lahir</dt><dd class="mt-1 text-sm text-slate-800">{{ $student->birth_place ?? '—' }}{{ $student->birth_date ? ', '.$student->birth_date->format('d/m/Y') : '' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Agama & Kewarganegaraan</dt><dd class="mt-1 text-sm text-slate-800">{{ $student->religion ?? 'Belum diisi' }} / {{ $student->citizenship ?? 'WNI' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">No. Telepon / HP / WA</dt><dd class="mt-1 text-sm font-mono text-slate-800">{{ $student->phone_number ?? 'Belum diisi' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama Orang Tua (Ayah / Ibu)</dt><dd class="mt-1 text-sm text-slate-800">{{ $student->father_name ?: ($student->mother_name ?: ($student->guardian_name ?? 'Belum diisi')) }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sekolah Asal</dt><dd class="mt-1 text-sm text-slate-800">{{ $student->previous_school_name ?? 'Belum diisi' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Alamat</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-800">{{ $student->address ?? 'Belum diisi' }}</dd></div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-6 py-5"><h3 class="font-semibold text-slate-950">Riwayat penempatan dan rapor</h3><p class="mt-1 text-sm text-slate-500">Riwayat tetap dipertahankan ketika peserta didik dinonaktifkan.</p></div>@if ($student->enrollments->isEmpty())<p class="p-6 text-sm text-slate-500">Belum ada penempatan.</p>@else<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-sm"><thead class="bg-slate-50"><tr><x-table.th>Kelas</x-table.th><x-table.th>Program / tingkat</x-table.th><x-table.th>Periode</x-table.th><x-table.th>Status penempatan</x-table.th><x-table.th>Rapor</x-table.th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($student->enrollments as $enrollment)<tr><x-table.td><p class="font-medium">{{ $enrollment->academicClass->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $enrollment->academicClass->code }}</p></x-table.td><x-table.td>{{ $enrollment->academicClass->program->name }} · {{ $enrollment->academicClass->level->name }}</x-table.td><x-table.td>{{ $enrollment->academicClass->academicYear->name }}</x-table.td><x-table.td>{{ $enrollment->enrollment_status === 'ACTIVE' ? 'Aktif' : $enrollment->enrollment_status }}</x-table.td><x-table.td>{{ $enrollment->reportCards->count() }} dokumen</x-table.td></tr>@endforeach</tbody></table></div>@endif</section>
        @can('delete', $student)<section class="rounded-2xl border border-rose-200 bg-rose-50 p-6"><h3 class="font-semibold text-rose-950">Zona data</h3><p class="mt-1 text-sm text-rose-800">Peserta didik yang memiliki riwayat penempatan akan dinonaktifkan agar riwayat dan rapornya tetap aman.</p><form method="POST" action="{{ route('admin.students.destroy', $student) }}" class="mt-4 flex justify-end">@csrf @method('DELETE')<x-button type="submit" variant="danger" icon="trash" confirm="Hapus atau nonaktifkan peserta didik ini?">Hapus / nonaktifkan</x-button></form></section>@endcan
    </div>
@endsection
