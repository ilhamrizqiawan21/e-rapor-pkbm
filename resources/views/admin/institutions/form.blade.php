@php
    $isEdit = $formMode === 'edit';
    $logoUrl = $institution->logo_path ? Storage::disk('public')->url($institution->logo_path) : null;
    $formAction = $isEdit ? route('admin.institutions.update', $institution) : route('admin.institutions.store');
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-6" x-data="{
    previewUrl: @js($logoUrl),
    removeLogo: false,
    setLogo(event) {
        const file = event.target.files[0];
        if (!file) return;
        this.removeLogo = false;
        this.previewUrl = URL.createObjectURL(file);
    },
    clearLogo() {
        this.removeLogo = true;
        this.previewUrl = null;
        this.$refs.logoInput.value = '';
    }
}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-slate-950">Informasi utama</h3>
            <p class="mt-1 text-sm text-slate-500">Data ini akan menjadi identitas utama PKBM.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-input name="name" label="Nama PKBM" :value="old('name', $institution->name)" :error="$errors->first('name')" required autofocus autocomplete="organization" placeholder="Contoh: PKBM Harapan Bangsa" /></div>
            <div class="sm:col-span-2"><x-textarea name="address" label="Alamat" :value="old('address', $institution->address)" :error="$errors->first('address')" rows="4" placeholder="Alamat lengkap PKBM" /></div>
            <x-input name="postal_code" label="Kode pos" :value="old('postal_code', $institution->postal_code)" :error="$errors->first('postal_code')" inputmode="numeric" autocomplete="postal-code" placeholder="Contoh: 40123" />
            <x-input name="phone" label="Telepon" :value="old('phone', $institution->phone)" :error="$errors->first('phone')" autocomplete="tel" placeholder="Contoh: 021-12345678" />
            <x-input name="email" type="email" label="Email resmi" :value="old('email', $institution->email)" :error="$errors->first('email')" autocomplete="email" placeholder="pkbm@example.org" />
            <x-input name="npsn" label="NPSN" :value="old('npsn', $institution->npsn)" :error="$errors->first('npsn')" placeholder="Nomor Pokok Satuan Pendidikan Nasional" />
            <div class="sm:col-span-2"><x-input name="permit_number" label="Nomor izin operasional" :value="old('permit_number', $institution->permit_number)" :error="$errors->first('permit_number')" placeholder="Nomor izin operasional PKBM" /></div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-slate-950">Logo PKBM</h3>
            <p class="mt-1 text-sm text-slate-500">JPG, PNG, atau WEBP. Ukuran maksimal 2 MB dan dimensi 100–2400 piksel.</p>
        </div>
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
            <div class="flex h-36 w-36 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-brand-50 ring-1 ring-brand-100">
                <template x-if="previewUrl">
                    <img :src="previewUrl" alt="Preview logo PKBM" class="h-full w-full object-contain p-3">
                </template>
                <template x-if="!previewUrl">
                    <span class="text-center text-xs font-medium text-slate-500">Belum ada logo</span>
                </template>
            </div>
            <div class="min-w-0 flex-1 space-y-3">
                <input x-ref="logoInput" @change="setLogo($event)" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-800 focus:outline-hidden focus:ring-2 focus:ring-brand-500/20">
                @if ($errors->has('logo'))
                    <p class="text-xs text-rose-600" role="alert">{{ $errors->first('logo') }}</p>
                @endif
                <input type="hidden" name="remove_logo" :value="removeLogo ? '1' : '0'">
                <div class="flex flex-wrap gap-2">
                    <x-button type="button" variant="cancel" size="sm" icon="trash" @click="clearLogo()">Hapus / bersihkan logo</x-button>
                    <p class="self-center text-xs text-slate-500">Pilih file baru untuk mengganti logo yang tersimpan.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="flex flex-wrap items-center justify-end gap-3">
        @if ($modalName ?? false)
            <x-button type="button" variant="cancel" icon="x" @click="$dispatch('close-modal', '{{ $modalName }}')">Batal</x-button>
        @else
            <x-button href="{{ $isEdit ? route('admin.institutions.show', $institution) : route('admin.institutions.index') }}" variant="cancel" icon="x">Batal</x-button>
        @endif
        <x-button type="submit" icon="save">{{ $isEdit ? 'Simpan perubahan' : 'Simpan identitas' }}</x-button>
    </div>
</form>
