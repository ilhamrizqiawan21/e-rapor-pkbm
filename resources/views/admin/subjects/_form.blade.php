<div class="grid gap-5 sm:grid-cols-2">
    <x-input name="code" label="Kode mata pelajaran" :value="old('code', $subject->code)" :error="$errors->first('code')" required hint="Huruf akan disimpan kapital. Contoh: BIND, MAT, PPKN." />
    <x-input name="short_name" label="Singkatan leger" :value="old('short_name', $subject->short_name)" :error="$errors->first('short_name')" required hint="Dipakai sebagai label ringkas pada leger dan tabel nilai." />
    <x-input name="name" label="Nama mata pelajaran" :value="old('name', $subject->name)" :error="$errors->first('name')" required placeholder="Contoh: Bahasa Indonesia" />
    <x-input name="category" label="Kategori" :value="old('category', $subject->category)" :error="$errors->first('category')" placeholder="Contoh: Umum, Keagamaan, Keterampilan" />
    <x-input name="display_order" type="number" min="1" max="9999" label="Urutan tampilan" :value="old('display_order', $subject->display_order ?? 1)" :error="$errors->first('display_order')" required hint="Menentukan urutan pada rapor dan leger." />
    <div class="flex items-center gap-3 self-end pb-2">
        <input type="hidden" name="active" value="0">
        <input id="subject-active" type="checkbox" name="active" value="1" @checked(old('active', $subject->active ?? true)) class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
        <label for="subject-active" class="text-sm font-medium text-slate-700">Mata pelajaran aktif</label>
    </div>
</div>
