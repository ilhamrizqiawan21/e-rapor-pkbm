@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <header class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ route('admin.students.import.create') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-950 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 18-6-6 6-6" /></svg>Ganti file</a>
                <p class="mt-5 text-xs font-bold uppercase tracking-[0.22em] text-accent-700">Peserta didik / import</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Tinjau hasil import</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Periksa baris yang akan masuk ke master peserta didik. Hanya baris valid yang dapat disimpan.</p>
            </div>
            @if ($preview['errors'] !== [])<x-button href="{{ route('admin.students.import.errors') }}" variant="secondary">Download error report</x-button>@endif
        </header>

        <nav aria-label="Tahapan import" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-6">
            <ol class="grid grid-cols-2 gap-3 sm:flex sm:min-w-[620px] sm:items-center sm:justify-between sm:gap-4">
                @foreach ([['number' => '01', 'label' => 'Siapkan template', 'state' => 'done'], ['number' => '02', 'label' => 'Upload & validasi', 'state' => 'done'], ['number' => '03', 'label' => 'Tinjau hasil', 'state' => 'current'], ['number' => '04', 'label' => 'Simpan data', 'state' => 'upcoming']] as $step)
                    <li class="flex items-center gap-3 {{ $loop->last ? '' : 'sm:flex-1' }}"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $step['state'] === 'current' ? 'bg-brand-900 text-white ring-4 ring-brand-100' : ($step['state'] === 'done' ? 'bg-accent-100 text-accent-800' : 'bg-slate-100 text-slate-500') }}">{{ $step['state'] === 'done' ? '✓' : $step['number'] }}</span><span class="whitespace-nowrap text-sm font-semibold {{ $step['state'] === 'current' ? 'text-brand-950' : 'text-slate-500' }}">{{ $step['label'] }}</span>@if (! $loop->last)<span class="hidden h-px flex-1 bg-slate-200 sm:block"></span>@endif</li>
                @endforeach
            </ol>
        </nav>

        <section aria-label="Ringkasan validasi" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['label' => 'Total baris', 'value' => $preview['total_count'], 'tone' => 'neutral'], ['label' => 'Siap diimport', 'value' => $preview['valid_count'], 'tone' => 'success'], ['label' => 'Perlu diperbaiki', 'value' => $preview['invalid_count'], 'tone' => 'danger'], ['label' => 'Baris kosong', 'value' => $preview['blank_count'], 'tone' => 'warning']] as $stat)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-600">{{ $stat['label'] }}</p><p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 tabular-nums">{{ $stat['value'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $stat['tone'] === 'success' ? 'Baris tanpa kesalahan' : ($stat['tone'] === 'danger' ? 'Tidak akan disimpan' : 'Ditemukan pada file') }}</p></article>
            @endforeach
        </section>

        @if ($preview['errors'] !== [])
            <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-900" role="alert"><svg class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 4.6 2.9 17a2 2 0 0 0 1.7 3h14.8a2 2 0 0 0 1.7-3L13.7 4.6a2 2 0 0 0-3.4 0Z" /></svg><div><p class="font-semibold">Sebagian baris belum siap</p><p class="mt-1 leading-5">Unduh error report untuk memperbaiki file. Baris valid tetap bisa disimpan tanpa mengubah data yang sudah ada.</p></div></div>
        @else
            <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-900" role="status"><svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" /></svg><div><p class="font-semibold">File siap disimpan</p><p class="mt-1 leading-5">Semua baris telah lolos validasi dan dapat ditambahkan ke master peserta didik.</p></div></div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="preview-table-heading">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6"><div><h2 id="preview-table-heading" class="text-lg font-semibold text-slate-950">Preview baris data</h2><p class="mt-1 text-sm text-slate-500">Baris merah memiliki kesalahan dan akan dilewati saat import.</p></div><form method="POST" action="{{ route('admin.students.import.store') }}" class="shrink-0">@csrf<input type="hidden" name="preview_token" value="{{ $token }}"><x-button type="submit" :disabled="$preview['valid_count'] === 0">Simpan {{ $preview['valid_count'] }} baris valid</x-button></form></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm"><caption class="sr-only">Preview data peserta didik yang akan diimport</caption><thead class="bg-slate-50"><tr><th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Baris</th>@foreach($preview['headers'] as $header)<th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $header }}</th>@endforeach<th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th></tr></thead><tbody class="divide-y divide-slate-100">
                    @foreach($preview['rows'] as $row)
                        <tr class="{{ $row['valid'] ? 'hover:bg-slate-50' : 'bg-rose-50/70' }}"><td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-500">{{ $row['row'] }}</td>@foreach($row['values'] as $value)<td class="max-w-56 whitespace-nowrap px-4 py-3 text-slate-700">{{ $value ?: '—' }}</td>@endforeach<td class="min-w-48 px-4 py-3">@if($row['valid'])<x-badge variant="success" dot>Valid</x-badge>@else<div><x-badge variant="danger" dot>Perlu diperbaiki</x-badge><p class="mt-1 max-w-xs text-xs leading-5 text-rose-700">{{ implode(' ', $row['errors']) }}</p></div>@endif</td></tr>
                    @endforeach
                </tbody></table>
            </div>
        </section>
    </div>
@endsection
