@extends('layouts.app')

@php
    $academicYear = $context['academicYear'];
    $semester = $context['semester'];
    $academicClass = $context['academicClass'];
    $assignment = $context['assignment'];
    $inputUrl = route('admin.grade-entry.index', [
        'academic_year_id' => $academicYear->id,
        'semester_id' => $semester->id,
        'class_id' => $academicClass->id,
        'subject_id' => $assignment->subject_id,
        'subject_assignment_id' => $assignment->id,
    ]);
    $contextParams = [
        'academic_year_id' => $academicYear->id,
        'semester_id' => $semester->id,
        'class_id' => $academicClass->id,
        'subject_id' => $assignment->subject_id,
        'subject_assignment_id' => $assignment->id,
    ];
@endphp

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Penilaian / impor nilai" title="Impor nilai dari Excel" description="Isi satu template untuk satu kelas dan satu mata pelajaran. Sistem memeriksa identitas, nilai, predikat, dan deskripsi sebelum menyimpan apa pun." :back-href="$inputUrl" back-text="Kembali ke input nilai">
            <x-button :href="route('admin.grade-entry.import.template', $contextParams)" variant="excel" icon="download">Unduh template resmi</x-button>
        </x-page-header>

        <nav aria-label="Tahapan impor" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-6">
            <ol class="grid grid-cols-2 gap-3 sm:flex sm:min-w-[620px] sm:items-center sm:justify-between sm:gap-4">
                @foreach ([['number' => '01', 'label' => 'Unduh template', 'state' => 'current'], ['number' => '02', 'label' => 'Isi nilai', 'state' => 'upcoming'], ['number' => '03', 'label' => 'Pratinjau pemeriksaan', 'state' => 'upcoming'], ['number' => '04', 'label' => 'Konfirmasi impor', 'state' => 'upcoming']] as $step)
                    <li class="flex items-center gap-3 {{ $loop->last ? '' : 'sm:flex-1' }}"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $step['state'] === 'current' ? 'bg-brand-900 text-white ring-4 ring-brand-100' : 'bg-slate-100 text-slate-500' }}">{{ $step['number'] }}</span><span class="whitespace-nowrap text-sm font-semibold {{ $step['state'] === 'current' ? 'text-brand-950' : 'text-slate-500' }}">{{ $step['label'] }}</span>@if (! $loop->last)<span class="hidden h-px flex-1 bg-slate-200 sm:block"></span>@endif</li>
                @endforeach
            </ol>
        </nav>

        <section class="rounded-2xl border border-brand-100 bg-brand-50/60 p-5 shadow-sm sm:p-6" aria-labelledby="context-heading">
            <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-900 text-accent-300"><x-icon name="audit" size="5" /></span><div><h2 id="context-heading" class="text-lg font-semibold text-brand-950">Konteks file terkunci</h2><p class="mt-1 text-sm leading-6 text-brand-900/75">File yang diunduh hanya berlaku untuk kombinasi berikut. Jangan menggabungkan nilai dari kelas atau mata pelajaran lain.</p></div></div>
            <dl class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([['Tahun pelajaran', $academicYear->name], ['Semester', $semester->name], ['Kelas', $academicClass->name], ['Mata pelajaran', $assignment->subject->name]] as $item)
                    <div class="rounded-xl border border-white/80 bg-white/80 px-4 py-3"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item[0] }}</dt><dd class="mt-1 truncate text-sm font-semibold text-slate-950">{{ $item[1] }}</dd></div>
                @endforeach
            </dl>
        </section>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(18rem,0.7fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="upload-heading">
                <div class="flex items-start gap-4"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><x-icon name="upload" size="5" /></span><div><h2 id="upload-heading" class="text-lg font-semibold text-slate-950">Unggah file nilai</h2><p class="mt-1 text-sm leading-6 text-slate-500">Unggah XLSX yang berasal dari template resmi pada konteks ini. Upload belum menyimpan nilai.</p></div></div>
                <form method="POST" action="{{ route('admin.grade-entry.import.preview') }}" enctype="multipart/form-data" class="mt-7 space-y-5">
                    @csrf
                    @foreach ($contextParams as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                    <div>
                        <label for="grade-import-file" class="block text-sm font-semibold text-slate-800">File XLSX <span class="text-rose-500">*</span></label>
                        <div class="mt-2 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-5 transition-colors hover:border-brand-400 hover:bg-brand-50/40 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/20">
                            <input id="grade-import-file" name="file" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required aria-describedby="grade-import-help" class="block w-full cursor-pointer text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-800">
                            <p id="grade-import-help" class="mt-3 text-xs leading-5 text-slate-500">Maksimal 10 MB. Gunakan satu file untuk satu konteks dan simpan dalam format XLSX.</p>
                        </div>
                        @error('file')<p class="mt-2 text-sm text-rose-600" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs leading-5 text-slate-500">Belum punya file? <a href="{{ route('admin.grade-entry.import.template', $contextParams) }}" class="font-semibold text-brand-700 hover:text-brand-950">Unduh template konteks ini</a>.</p><x-button type="submit" icon="eye">Validasi dan preview</x-button></div>
                </form>
            </section>

            <aside class="space-y-4">
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5" aria-labelledby="rules-heading"><div class="flex items-start gap-3"><x-icon name="alert" class="mt-0.5 text-amber-700" size="5" /><div><h2 id="rules-heading" class="font-semibold text-amber-950">Yang boleh diubah</h2><ul class="mt-3 space-y-2 text-sm leading-5 text-amber-900"><li>• MATERI di sheet KONTEKS.</li><li>• NILAI pada sheet NILAI.</li><li>• Nilai kosong berarti tidak mengubah nilai lama.</li></ul></div></div></section>
                <section class="rounded-2xl border border-slate-200 bg-slate-900 p-5 text-white" aria-labelledby="server-heading"><h2 id="server-heading" class="font-semibold">Yang dibuat server</h2><p class="mt-2 text-sm leading-5 text-slate-300">Enrollment, NIPD, NISN, nama, predikat, deskripsi otomatis, status, periode, dan mata pelajaran akan dicocokkan ulang. Kolom hasil di Excel tidak dipercaya.</p></section>
            </aside>
        </div>
    </div>
@endsection
