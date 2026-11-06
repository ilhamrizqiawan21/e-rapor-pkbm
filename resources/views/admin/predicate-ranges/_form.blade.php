@csrf
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-input name="code" label="Kode predikat" :value="old('code', $range->code)" hint="Contoh: A, B, C, D." required />
        <x-input name="label" label="Label" :value="old('label', $range->label)" hint="Contoh: Sangat Baik." required />
        <x-input name="minimum_score" type="number" min="0" max="100" label="Nilai minimum" :value="old('minimum_score', $range->minimum_score)" required />
        <x-input name="maximum_score" type="number" min="0" max="100" label="Nilai maksimum" :value="old('maximum_score', $range->maximum_score)" required />
        <x-input name="display_order" type="number" min="1" max="9999" label="Urutan tampil" :value="old('display_order', $range->display_order ?: 1)" required />
    </div>
    <label class="mt-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
        <input type="hidden" name="active" value="0">
        <input class="mt-0.5 rounded border-slate-300 text-brand-700 focus:ring-brand-600" type="checkbox" name="active" value="1" @checked(old('active', $range->active ?? true))>
        <span><span class="font-semibold text-slate-900">Rentang aktif</span><span class="mt-1 block text-xs text-slate-500">Hanya rentang aktif yang dipakai untuk konversi nilai baru.</span></span>
    </label>
</section>
<div class="flex flex-wrap justify-end gap-3"><x-button type="submit">Simpan rentang</x-button><x-button href="{{ route('admin.predicate-ranges.index') }}" variant="secondary">Batal</x-button></div>
