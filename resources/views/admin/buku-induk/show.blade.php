@extends('layouts.app')

@section('content')
    @php
        $student = $data['student'];
        $institution = $data['institution'];
        $records = $data['records'];
        $statusLabels = ['ACTIVE' => 'Aktif Belajar', 'INACTIVE' => 'Nonaktif', 'GRADUATED' => 'Lulus', 'TRANSFERRED' => 'Pindah / Mutasi'];
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-header
            eyebrow="Buku Induk Warga Belajar"
            title="{{ $student->full_name }}"
            description="NIPD: {{ $student->student_number }} · NISN: {{ $student->national_student_number ?: '—' }} · NIK: {{ $student->nik ?: '—' }}"
            back-href="{{ route('admin.buku-induk.index') }}"
            back-text="Kembali ke Buku Induk"
        >
            <div class="flex flex-wrap items-center gap-2">
                <x-button href="{{ route('admin.students.edit', $student) }}" variant="secondary" icon="edit">
                    Edit Biodata
                </x-button>
                <x-button href="{{ route('admin.buku-induk.sheet-print', $student) }}" variant="secondary" icon="printer" target="_blank">
                    Print Langsung
                </x-button>
                <x-button href="{{ route('admin.buku-induk.sheet-pdf', $student) }}" variant="primary" icon="download" target="_blank">
                    Unduh Lembar PDF
                </x-button>
            </div>
        </x-page-header>

        {{-- Status Card --}}
        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status Keaktifan</p>
                <div class="mt-3 flex items-center gap-2">
                    <x-badge :variant="$student->status" dot>{{ $statusLabels[$student->status] ?? $student->status }}</x-badge>
                    @if ($student->graduation_certificate_number)
                        <span class="text-xs text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded font-mono">No Ijazah: {{ $student->graduation_certificate_number }}</span>
                    @endif
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Riwayat Belajar</p>
                <p class="mt-3 text-2xl font-semibold text-slate-950">{{ count($records) }} Periode Rapor</p>
                <p class="mt-1 text-xs text-slate-500">{{ $student->enrollments->count() }} kali penempatan kelas</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Diterima Masuk</p>
                <p class="mt-3 text-lg font-semibold text-slate-950">{{ $student->entry_date ? $student->entry_date->translatedFormat('d F Y') : ($student->created_at ? $student->created_at->translatedFormat('d F Y') : '—') }}</p>
                <p class="mt-1 text-xs text-slate-500">Asal: {{ $student->previous_school_name ?: '—' }}</p>
            </div>
        </section>

        {{-- BIODATA BUKU INDUK --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 space-y-8">
            {{-- Bagian A --}}
            <div>
                <h3 class="text-base font-semibold text-slate-950 border-b border-slate-100 pb-3 mb-4">
                    A. Keterangan Pribadi Peserta Didik
                </h3>
                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <dl class="flex-1 grid gap-5 sm:grid-cols-2 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama Lengkap</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $student->full_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama Panggilan</dt>
                            <dd class="mt-1 text-slate-800">{{ $student->nickname ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">NIPD / Nomor Induk Satuan</dt>
                            <dd class="mt-1 font-mono text-slate-800">{{ $student->student_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">NISN</dt>
                            <dd class="mt-1 font-mono text-slate-800">{{ $student->national_student_number ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">NIK (Nomor Induk Kependudukan)</dt>
                            <dd class="mt-1 font-mono text-slate-800">{{ $student->nik ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor Kartu Keluarga (KK)</dt>
                            <dd class="mt-1 font-mono text-slate-800">{{ $student->family_card_number ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis Kelamin</dt>
                            <dd class="mt-1 text-slate-800">{{ $student->gender === 'L' ? 'Laki-laki' : ($student->gender === 'P' ? 'Perempuan' : '—') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tempat, Tanggal Lahir</dt>
                            <dd class="mt-1 text-slate-800">{{ $student->birth_place ?: '—' }}{{ $student->birth_date ? ', '.$student->birth_date->translatedFormat('d F Y') : '' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Agama & Kewarganegaraan</dt>
                            <dd class="mt-1 text-slate-800">{{ $student->religion ?: '—' }} / {{ $student->citizenship ?: 'WNI' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Anak ke- / Jumlah Saudara</dt>
                            <dd class="mt-1 text-slate-800">Anak ke-{{ $student->birth_order ?: '—' }} dari {{ $student->siblings_count !== null ? $student->siblings_count.' bersaudara' : '—' }}</dd>
                        </div>
                    </dl>
                    <div class="w-36 shrink-0 flex flex-col items-center justify-center p-3 border border-dashed border-slate-300 rounded-xl bg-slate-50 text-center self-center md:self-start">
                        <div class="w-[30mm] h-[40mm] flex items-center justify-center text-xs font-semibold text-slate-400">
                            PAS FOTO<br>3 &times; 4
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian B --}}
            <div>
                <h3 class="text-base font-semibold text-slate-950 border-b border-slate-100 pb-3">
                    B. Keterangan Tempat Tinggal & Kontak
                </h3>
                <dl class="mt-4 grid gap-5 sm:grid-cols-2 text-sm">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Alamat Tempat Tinggal</dt>
                        <dd class="mt-1 whitespace-pre-line text-slate-800">{{ $student->address ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode Pos</dt>
                        <dd class="mt-1 font-mono text-slate-800">{{ $student->postal_code ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">No. Telepon / WhatsApp</dt>
                        <dd class="mt-1 font-mono text-slate-800">{{ $student->phone_number ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tinggal Bersama</dt>
                        <dd class="mt-1 text-slate-800">{{ $student->living_with ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Moda Transportasi</dt>
                        <dd class="mt-1 text-slate-800">{{ $student->transportation_mode ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Bagian C --}}
            <div>
                <h3 class="text-base font-semibold text-slate-950 border-b border-slate-100 pb-3">
                    C. Keterangan Orang Tua Kandung & Wali
                </h3>
                <div class="mt-4 grid gap-5 sm:grid-cols-3 text-sm">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                        <p class="font-semibold text-slate-900 mb-2">Ayah Kandung</p>
                        <dl class="space-y-2 text-xs">
                            <div><dt class="text-slate-500">Nama:</dt><dd class="font-medium text-slate-800">{{ $student->father_name ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">NIK:</dt><dd class="font-mono text-slate-700">{{ $student->father_nik ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">Pendidikan / Pekerjaan:</dt><dd class="text-slate-700">{{ $student->father_education ?: '—' }} / {{ $student->father_occupation ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">Penghasilan:</dt><dd class="text-slate-700">{{ $student->father_income ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">No. HP:</dt><dd class="font-mono text-slate-700">{{ $student->father_phone ?: '—' }}</dd></div>
                        </dl>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                        <p class="font-semibold text-slate-900 mb-2">Ibu Kandung</p>
                        <dl class="space-y-2 text-xs">
                            <div><dt class="text-slate-500">Nama:</dt><dd class="font-medium text-slate-800">{{ $student->mother_name ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">NIK:</dt><dd class="font-mono text-slate-700">{{ $student->mother_nik ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">Pendidikan / Pekerjaan:</dt><dd class="text-slate-700">{{ $student->mother_education ?: '—' }} / {{ $student->mother_occupation ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">Penghasilan:</dt><dd class="text-slate-700">{{ $student->mother_income ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">No. HP:</dt><dd class="font-mono text-slate-700">{{ $student->mother_phone ?: '—' }}</dd></div>
                        </dl>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                        <p class="font-semibold text-slate-900 mb-2">Wali</p>
                        <dl class="space-y-2 text-xs">
                            <div><dt class="text-slate-500">Nama:</dt><dd class="font-medium text-slate-800">{{ $student->guardian_name ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">Hubungan:</dt><dd class="text-slate-700">{{ $student->guardian_relation ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">Pekerjaan:</dt><dd class="text-slate-700">{{ $student->guardian_occupation ?: '—' }}</dd></div>
                            <div><dt class="text-slate-500">No. HP:</dt><dd class="font-mono text-slate-700">{{ $student->guardian_phone ?: '—' }}</dd></div>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Bagian D --}}
            <div>
                <h3 class="text-base font-semibold text-slate-950 border-b border-slate-100 pb-3">
                    D. Pendidikan Sebelumnya & Penerimaan di Satuan Pendidikan
                </h3>
                <dl class="mt-4 grid gap-5 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sekolah / Lembaga Asal</dt>
                        <dd class="mt-1 text-slate-800">{{ $student->previous_school_name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor Ijazah Sebelumnya</dt>
                        <dd class="mt-1 font-mono text-slate-800">{{ $student->previous_diploma_number ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Resmi Diterima</dt>
                        <dd class="mt-1 text-slate-800">{{ $student->entry_date ? $student->entry_date->translatedFormat('d F Y') : ($student->created_at ? $student->created_at->translatedFormat('d F Y') : '—') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nomor Seri Ijazah PKBM (Saat Lulus)</dt>
                        <dd class="mt-1 font-mono text-emerald-800 font-semibold">{{ $student->graduation_certificate_number ?: 'Belum terbit' }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        {{-- REKAP PERKEMBANGAN BELAJAR & NILAI RAPOR BUKU INDUK --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="font-semibold text-slate-950">E. Catatan Perkembangan Belajar & Nilai Rapor Kumulatif</h3>
                <p class="mt-1 text-sm text-slate-500">Rekapitulasi nilai dan capaian akademik dari seluruh semester yang telah dilalui.</p>
            </div>

            @if (empty($records))
                <div class="p-8 text-center text-slate-500">
                    <p class="text-sm">Belum ada dokumen rapor atau nilai akhir yang tersimpan untuk peserta didik ini.</p>
                </div>
            @else
                <div class="p-6 space-y-6">
                    @foreach ($records as $record)
                        <div class="border border-slate-200 rounded-xl overflow-hidden">
                            <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2 text-sm">
                                <div>
                                    <span class="font-semibold text-slate-900">{{ $record['academic_year'] }} · Semester {{ $record['semester'] }}</span>
                                    <span class="text-slate-400 mx-2">|</span>
                                    <span class="text-slate-600">{{ $record['class_name'] }} ({{ $record['program'] }} / {{ $record['level'] }})</span>
                                </div>
                                <div class="text-xs text-slate-500">
                                    Wali Kelas: <span class="font-medium text-slate-700">{{ $record['homeroom_teacher'] }}</span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100 text-xs">
                                    <thead class="bg-slate-100/60 font-semibold text-slate-700">
                                        <tr>
                                            <th class="py-2 px-3 text-left w-12">No</th>
                                            <th class="py-2 px-3 text-left">Kode & Mata Pelajaran</th>
                                            <th class="py-2 px-3 text-center w-20">KKM</th>
                                            <th class="py-2 px-3 text-center w-24">Nilai Akhir</th>
                                            <th class="py-2 px-3 text-center w-20">Predikat</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @php($gNo = 1)
                                        @foreach ($record['grades'] as $grade)
                                            <tr>
                                                <td class="py-2 px-3 text-slate-400 font-mono">{{ $gNo++ }}</td>
                                                <td class="py-2 px-3 text-slate-800 font-medium">
                                                    {{ $grade->subject_name_snapshot ?: ($grade->subjectAssignment?->subject?->name ?: '—') }}
                                                    <span class="text-slate-400 font-mono text-[11px] block">{{ $grade->subject_code_snapshot ?: ($grade->subjectAssignment?->subject?->code ?: '') }}</span>
                                                </td>
                                                <td class="py-2 px-3 text-center font-mono text-slate-600">{{ $grade->minimum_score_snapshot ?: ($grade->kkm_snapshot ?: '—') }}</td>
                                                <td class="py-2 px-3 text-center font-mono font-semibold text-slate-900">{{ $grade->score !== null ? $grade->score : '—' }}</td>
                                                <td class="py-2 px-3 text-center font-semibold text-slate-700">{{ $grade->predicate_snapshot ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-slate-50/80 font-semibold">
                                            <td colspan="3" class="py-2 px-3 text-right text-slate-600">Rata-rata Semester:</td>
                                            <td class="py-2 px-3 text-center font-mono text-accent-700">{{ $record['average'] ?? '—' }}</td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="bg-slate-50/50 p-3 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-600 gap-2">
                                <div>
                                    <span class="font-semibold text-slate-700">Kehadiran:</span>
                                    Sakit: {{ $record['sick'] ?? 0 }} hari · Izin: {{ $record['leave'] ?? 0 }} hari · Alpa: {{ $record['unexcused'] ?? 0 }} hari
                                </div>
                                @if (!empty($record['decision']))
                                    <div class="font-semibold text-accent-700">
                                        Keputusan: {{ $record['decision'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
