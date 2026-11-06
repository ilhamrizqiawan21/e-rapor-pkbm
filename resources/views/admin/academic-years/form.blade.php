@php
    $isEdit = $academicYear->exists;
    $formAction = $isEdit ? route('admin.academic-years.update', $academicYear) : route('admin.academic-years.store');
    $statusValue = old('status', $academicYear->status?->value ?? 'DRAFT');
@endphp

<form method="POST" action="{{ $formAction }}" class="space-y-6" x-data="{ status: @js($statusValue) }">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-6"><h3 class="text-lg font-semibold text-slate-950">Informasi periode</h3><p class="mt-1 text-sm text-slate-500">Tanggal periode digunakan sebagai acuan kelas dan rapor.</p></div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-input name="name" label="Nama tahun pelajaran" :value="old('name', $academicYear->name)" :error="$errors->first('name')" required placeholder="Contoh: 2025/2026" /></div>
            <x-input name="start_date" type="date" label="Tanggal mulai" :value="old('start_date', $academicYear->start_date?->format('Y-m-d'))" :error="$errors->first('start_date')" required />
            <x-input name="end_date" type="date" label="Tanggal selesai" :value="old('end_date', $academicYear->end_date?->format('Y-m-d'))" :error="$errors->first('end_date')" required />
            <div class="sm:col-span-2"><x-select name="status" label="Status" :selected="$statusValue" :error="$errors->first('status')" required @change="status = $event.target.value">
                @foreach ($statusOptions as $statusOption)
                    <option value="{{ $statusOption->value }}" @selected($statusValue === $statusOption->value)>{{ $statusOption->label() }}</option>
                @endforeach
            </x-select></div>
            <label x-show="status === 'CLOSED'" class="sm:col-span-2 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <input type="checkbox" name="confirm_closure" value="1" @checked(old('confirm_closure')) class="mt-0.5 rounded border-amber-400 text-amber-700 focus:ring-amber-500">
                <span>Saya memahami bahwa periode ini akan ditutup dan tidak dapat menerima perubahan semester.</span>
            </label>
        </div>
    </section>
    <div class="flex flex-wrap justify-end gap-3"><x-button href="{{ $isEdit ? route('admin.academic-years.show', $academicYear) : route('admin.academic-years.index') }}" variant="cancel" icon="x">Batal</x-button><x-button type="submit" icon="save">{{ $isEdit ? 'Simpan perubahan' : 'Simpan tahun pelajaran' }}</x-button></div>
</form>
