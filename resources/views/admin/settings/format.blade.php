@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-header eyebrow="Pengaturan PKBM / rapor" title="Format rapor" description="Atur tampilan dan isi dokumen rapor." back-href="{{ route('admin.institutions.index') }}" back-text="Kembali ke pengaturan PKBM" />

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ url('/admin/settings/format') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-5">
                    <h3 class="text-lg font-semibold text-slate-950">Format teks</h3>
                    <p class="mt-1 text-sm text-slate-600">Gunakan token yang tersedia agar format dapat dipakai ulang oleh modul rapor.</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="student_name_format" label="Format nama Peserta Didik" :value="old('student_name_format', $settings->student_name_format)" :error="$errors->first('student_name_format')" required hint="Token: {full_name}, {student_number}, {national_student_number}" />
                    <x-input name="document_number_format" label="Format nomor dokumen" :value="old('document_number_format', $settings->document_number_format)" :error="$errors->first('document_number_format')" required hint="Token: {document_number}, {academic_year}, {semester}, {student_number}" />
                    <x-input name="date_format" label="Format tanggal" :value="old('date_format', $settings->date_format)" :error="$errors->first('date_format')" required hint="Contoh: d/m/Y atau d F Y" />
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-5">
                    <h3 class="text-lg font-semibold text-slate-950">Tanda tangan</h3>
                    <p class="mt-1 text-sm text-slate-600">Atur peran dan label area tanda tangan. Data identitas penandatangan dikelola pada modul tersendiri.</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-select name="signature_left_role" label="Peran sisi kiri" :options="['GURU' => 'Guru / Wali Kelas', 'KEPSEK' => 'Kepala PKBM', 'ADMIN' => 'Admin']" :selected="old('signature_left_role', $settings->signature_left_role)" :error="$errors->first('signature_left_role')" required />
                    <x-input name="signature_left_label" label="Label sisi kiri" :value="old('signature_left_label', $settings->signature_left_label)" :error="$errors->first('signature_left_label')" required />
                    <x-select name="signature_right_role" label="Peran sisi kanan" :options="['KEPSEK' => 'Kepala PKBM', 'GURU' => 'Guru / Wali Kelas', 'ADMIN' => 'Admin']" :selected="old('signature_right_role', $settings->signature_right_role)" :error="$errors->first('signature_right_role')" required />
                    <x-input name="signature_right_label" label="Label sisi kanan" :value="old('signature_right_label', $settings->signature_right_label)" :error="$errors->first('signature_right_label')" required />
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-5">
                    <h3 class="text-lg font-semibold text-slate-950">Logo dan PDF</h3>
                    <p class="mt-1 text-sm text-slate-600">Logo mengambil identitas PKBM yang sudah tersedia. Di sini hanya diatur tampilannya pada dokumen.</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-2 lg:col-span-2">
                        <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                            <input type="hidden" name="show_logo" value="0">
                            <input type="checkbox" name="show_logo" value="1" @checked(old('show_logo', $settings->show_logo)) class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            Tampilkan logo pada rapor
                        </label>
                        @error('show_logo')<p class="text-xs text-rose-600" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <x-select name="logo_position" label="Posisi logo" :options="['left' => 'Kiri', 'center' => 'Tengah', 'right' => 'Kanan']" :selected="old('logo_position', $settings->logo_position)" :error="$errors->first('logo_position')" required />
                    <x-input name="logo_width_mm" type="number" label="Lebar logo (mm)" :value="old('logo_width_mm', $settings->logo_width_mm)" :error="$errors->first('logo_width_mm')" required min="10" max="60" />
                </div>
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <x-input name="margin_top_mm" type="number" label="Margin atas (mm)" :value="old('margin_top_mm', $settings->margin_top_mm)" :error="$errors->first('margin_top_mm')" required min="5" max="40" />
                    <x-input name="margin_right_mm" type="number" label="Margin kanan (mm)" :value="old('margin_right_mm', $settings->margin_right_mm)" :error="$errors->first('margin_right_mm')" required min="5" max="40" />
                    <x-input name="margin_bottom_mm" type="number" label="Margin bawah (mm)" :value="old('margin_bottom_mm', $settings->margin_bottom_mm)" :error="$errors->first('margin_bottom_mm')" required min="5" max="40" />
                    <x-input name="margin_left_mm" type="number" label="Margin kiri (mm)" :value="old('margin_left_mm', $settings->margin_left_mm)" :error="$errors->first('margin_left_mm')" required min="5" max="40" />
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-5">
                    <h3 class="text-lg font-semibold text-slate-950">Header, footer, dan kolom nilai</h3>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-textarea name="header_text" label="Teks header tambahan" :value="old('header_text', $settings->header_text)" :error="$errors->first('header_text')" rows="4" hint="Opsional. Identitas utama tetap memakai data PKBM." />
                    <x-textarea name="footer_text" label="Teks footer" :value="old('footer_text', $settings->footer_text)" :error="$errors->first('footer_text')" rows="4" hint="Opsional. Dapat diisi catatan atau disclaimer." />
                </div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                        <input type="hidden" name="show_kkm" value="0">
                        <input type="checkbox" name="show_kkm" value="1" @checked(old('show_kkm', $settings->show_kkm)) class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Tampilkan KKM / nilai minimum
                    </label>
                    <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                        <input type="hidden" name="show_predicate" value="0">
                        <input type="checkbox" name="show_predicate" value="1" @checked(old('show_predicate', $settings->show_predicate)) class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Tampilkan predikat
                    </label>
                </div>
            </section>

            <div class="flex justify-end">
                <x-button type="submit" size="lg" icon="save">Simpan pengaturan format</x-button>
            </div>
        </form>
    </div>
@endsection
