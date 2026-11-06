@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header eyebrow="Pengaturan PKBM / rapor" title="Penandatangan rapor" description="Atur sumber data Kepala PKBM yang akan disalin ke snapshot rapor saat rapor dikunci." back-href="{{ route('dashboard') }}" back-text="Kembali ke dashboard" />

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ url()->current() }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ source: @js(old('headmaster_source', $setting->headmaster_source?->value ?? 'ACCOUNT')) }">
            @csrf
            @method('PUT')

            <div>
                <x-select name="headmaster_source" label="Sumber Kepala PKBM" :selected="old('headmaster_source', $setting->headmaster_source?->value ?? 'ACCOUNT')" x-model="source" required>
                    @foreach ($sources as $sourceOption)
                        <option value="{{ $sourceOption->value }}">{{ $sourceOption->label() }}</option>
                    @endforeach
                </x-select>
            </div>

            <div x-cloak x-show="source === 'ACCOUNT'" class="rounded-xl border border-sky-200 bg-sky-50 p-4">
                <x-select name="headmaster_user_id" label="Akun Kepala PKBM" :selected="old('headmaster_user_id', $setting->headmaster_user_id)" class="border-sky-300 focus:border-sky-500 focus:ring-sky-500">
                    <option value="">Pilih akun Kepala PKBM</option>
                    @foreach ($kepsekUsers as $kepsek)
                        <option value="{{ $kepsek->getKey() }}" @selected((string) old('headmaster_user_id', $setting->headmaster_user_id) === (string) $kepsek->getKey())>
                            {{ $kepsek->name }}{{ $kepsek->staffProfile?->employee_number ? ' · '.$kepsek->staffProfile->employee_number : '' }}
                        </option>
                    @endforeach
                </x-select>
                <p class="mt-2 text-xs text-sky-800">Nama, jabatan, dan NIP/NIY akan diambil dari akun dan StaffProfile terpilih.</p>
                @error('headmaster_user_id')<p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
            </div>

            <div x-cloak x-show="source === 'MANUAL'" class="space-y-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-semibold text-amber-950">Data manual pada rapor</p>
                <x-input name="headmaster_name" label="Nama Kepala PKBM" :value="old('headmaster_name', $setting->headmaster_name)" :error="$errors->first('headmaster_name')" />
                <x-input name="headmaster_title" label="Jabatan" :value="old('headmaster_title', $setting->headmaster_title)" :error="$errors->first('headmaster_title')" />
                <x-input name="headmaster_employee_number" label="NIP/NIY" :value="old('headmaster_employee_number', $setting->headmaster_employee_number)" :error="$errors->first('headmaster_employee_number')" />
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <p class="mr-auto text-xs text-slate-500">Wali kelas diambil dari akun Guru pada kelas peserta didik. Orang tua/wali diambil dari data peserta didik.</p>
                <x-button type="submit">Simpan pengaturan</x-button>
            </div>
        </form>
    </div>
@endsection
