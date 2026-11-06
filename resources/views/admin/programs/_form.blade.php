<div class="grid gap-5 sm:grid-cols-2">
    <x-input name="code" label="Kode program" :value="old('code', $program->code)" :error="$errors->first('code')" required hint="Contoh: PA, PB, PC. Huruf akan disimpan kapital." />
    <x-input name="name" label="Nama program" :value="old('name', $program->name)" :error="$errors->first('name')" required placeholder="Contoh: Paket A" />
</div>
