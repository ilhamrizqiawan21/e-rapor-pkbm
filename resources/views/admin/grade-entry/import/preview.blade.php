@extends('layouts.app')

@php
    $context = $preview['context'];
    $inputUrl = route('admin.grade-entry.index', [
        'academic_year_id' => $context['academic_year_id'],
        'semester_id' => $context['semester_id'],
        'class_id' => $context['class_id'],
        'subject_assignment_id' => $context['subject_assignment_id'],
    ]);
    $uploadUrl = route('admin.grade-entry.import.create', [
        'academic_year_id' => $context['academic_year_id'],
        'semester_id' => $context['semester_id'],
        'class_id' => $context['class_id'],
        'subject_assignment_id' => $context['subject_assignment_id'],
    ]);
@endphp

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Penilaian / impor nilai" title="Pratinjau nilai sebelum disimpan" description="Hasil di bawah sudah dihitung ulang oleh server. Tidak ada baris yang masuk ke rapor sampai Anda menekan konfirmasi impor." :back-href="$uploadUrl" back-text="Ganti file">
            @if ($preview['errors'] !== [])<x-button href="{{ route('admin.grade-entry.import.errors', ['token' => $batch->token]) }}" variant="excel" icon="download">Unduh daftar kesalahan</x-button>@endif
        </x-page-header>

        <section class="rounded-2xl border border-brand-100 bg-brand-50/60 p-5 shadow-sm sm:p-6"><div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Konteks terverifikasi</p><h2 class="mt-1 text-lg font-semibold text-brand-950">{{ $context['class_name'] }} · {{ $context['subject_name'] }}</h2><p class="mt-1 text-sm text-brand-900/75">{{ $context['academic_year_name'] }} · {{ $context['semester_name'] }} · Materi: {{ $preview['material'] ?: 'Belum diisi' }}</p></div><x-badge variant="info" dot>Pratinjau {{ $batch->created_at?->diffForHumans() }}</x-badge></div></section>

        <nav aria-label="Tahapan impor" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-6"><ol class="grid grid-cols-2 gap-3 sm:flex sm:min-w-[620px] sm:items-center sm:justify-between sm:gap-4">@foreach ([['number' => '01', 'label' => 'Unduh template'], ['number' => '02', 'label' => 'Isi nilai'], ['number' => '03', 'label' => 'Pratinjau pemeriksaan'], ['number' => '04', 'label' => 'Konfirmasi impor']] as $step)<li class="flex items-center gap-3 {{ $loop->last ? '' : 'sm:flex-1' }}"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $loop->index < 3 ? 'bg-accent-100 text-accent-800' : 'bg-brand-900 text-white ring-4 ring-brand-100' }}">{{ $loop->index < 3 ? '✓' : $step['number'] }}</span><span class="whitespace-nowrap text-sm font-semibold {{ $loop->last ? 'text-brand-950' : 'text-slate-500' }}">{{ $step['label'] }}</span>@if (! $loop->last)<span class="hidden h-px flex-1 bg-slate-200 sm:block"></span>@endif</li>@endforeach</ol></nav>

        <section aria-label="Ringkasan validasi" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['label' => 'Total baris', 'value' => $preview['total_count'], 'hint' => 'Data siswa pada file'], ['label' => 'Siap diproses', 'value' => $preview['valid_count'], 'hint' => 'Tidak ada kesalahan'], ['label' => 'Akan diperbarui', 'value' => $preview['update_count'], 'hint' => 'Nilai terisi'], ['label' => 'Tidak diubah', 'value' => $preview['skip_count'], 'hint' => 'Nilai kosong atau tetap sama']] as $stat)<article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-600">{{ $stat['label'] }}</p><p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 tabular-nums">{{ $stat['value'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $stat['hint'] }}</p></article>@endforeach
        </section>

        @if ($preview['errors'] !== [])
            <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-900" role="alert"><x-icon name="alert" class="mt-0.5 shrink-0 text-rose-600" size="5" /><div><p class="font-semibold">Perbaiki file sebelum impor</p><p class="mt-1 leading-5">Ada {{ $preview['invalid_count'] }} baris bermasalah. Seluruh kumpulan data ditahan agar tidak ada impor setengah jalan. Unduh laporan kesalahan, perbaiki file asli, lalu lakukan pratinjau ulang.</p></div></div>
        @else
            <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-900" role="status"><x-icon name="check" class="mt-0.5 shrink-0 text-emerald-600" size="5" /><div><p class="font-semibold">Semua baris lolos validasi</p><p class="mt-1 leading-5">Predikat dan deskripsi akan ditulis ulang berdasarkan nilai serta materi saat impor dikonfirmasi.</p></div></div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="preview-table-heading"><div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6"><div><h2 id="preview-table-heading" class="text-lg font-semibold text-slate-950">Pratinjau baris nilai</h2><p class="mt-1 text-sm text-slate-500">Predikat dan deskripsi di sini adalah hasil perhitungan sistem.</p></div><div class="flex flex-wrap gap-2"><x-button href="{{ $inputUrl }}" variant="secondary" icon="arrow-left">Kembali ke input</x-button>@if ($preview['errors'] === [])<form method="POST" action="{{ route('admin.grade-entry.import.store') }}">@csrf<input type="hidden" name="token" value="{{ $batch->token }}"><x-button type="submit" icon="save" confirm="Impor {{ $preview['update_count'] }} nilai ke rapor? Proses ini dijalankan dalam satu langkah dan akan menolak seluruh kumpulan data bila konteks berubah.">Konfirmasi impor</x-button></form>@endif</div></div>
            <div class="overflow-x-auto"><table class="min-w-[1180px] divide-y divide-slate-200 text-sm"><caption class="sr-only">Pratinjau nilai dan deskripsi otomatis</caption><thead class="bg-slate-50"><tr>@foreach (['Baris', 'Peserta didik', 'Nilai', 'Predikat', 'Deskripsi otomatis', 'Aksi'] as $header)<th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $header }}</th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($preview['rows'] as $row)
                    <tr class="align-top {{ $row['valid'] ? 'hover:bg-slate-50' : 'bg-rose-50/70' }}"><td class="whitespace-nowrap px-4 py-4 font-mono text-xs text-slate-500">{{ $row['row'] }}</td><td class="px-4 py-4"><p class="font-semibold text-slate-900">{{ $row['full_name'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $row['nipd'] }}{{ $row['nisn'] ? ' · NISN '.$row['nisn'] : '' }}</p></td><td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-900">{{ $row['score'] ?? '—' }}</td><td class="whitespace-nowrap px-4 py-4">@if ($row['predicate'])<x-badge variant="success" dot>{{ $row['predicate'] }} · {{ $row['predicate_label'] }}</x-badge>@else<span class="text-slate-400">—</span>@endif</td><td class="min-w-[25rem] px-4 py-4 text-slate-700">{{ $row['description'] ?: 'Tidak ada perubahan untuk baris ini.' }}</td><td class="min-w-[15rem] px-4 py-4">@if ($row['valid'])<x-badge variant="{{ $row['action'] === 'UPDATE' ? 'success' : 'info' }}" dot>{{ $row['action'] === 'UPDATE' ? 'Akan diperbarui' : 'Dilewati' }}</x-badge>@else<div><x-badge variant="danger" dot>Perlu diperbaiki</x-badge><p class="mt-2 text-xs leading-5 text-rose-700">{{ implode(' ', $row['errors']) }}</p></div>@endif</td></tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Tidak ada baris nilai yang dapat diproses.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </div>
@endsection
