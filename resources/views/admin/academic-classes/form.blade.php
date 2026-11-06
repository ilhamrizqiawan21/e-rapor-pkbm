@csrf
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-input name="code" label="Kode kelas" :value="old('code', $academicClass->code)" placeholder="Contoh: A-1-2026" hint="Unik di dalam tahun pelajaran." required />
        <x-input name="name" label="Nama kelas" :value="old('name', $academicClass->name)" placeholder="Contoh: Kelas 1 A" required />
        <x-select name="academic_year_id" label="Tahun pelajaran" :options="$academicYears->pluck('name', 'id')->all()" :selected="old('academic_year_id', $academicClass->academic_year_id)" placeholder="Pilih tahun pelajaran" required />
        <x-select name="program_id" label="Program" :options="$programs->pluck('name', 'id')->all()" :selected="old('program_id', $academicClass->program_id)" placeholder="Pilih program" required />
        <x-select name="level_id" label="Tingkat" :selected="old('level_id', $academicClass->level_id)" placeholder="Pilih tingkat" hint="Tingkat harus berasal dari program yang dipilih." required>
            @foreach ($levels as $level)
                <option value="{{ $level->id }}" @selected((string) old('level_id', $academicClass->level_id) === (string) $level->id)>{{ $level->name }} · {{ $level->program->name }}</option>
            @endforeach
        </x-select>
        <x-select name="homeroom_teacher_id" label="Wali kelas" :selected="old('homeroom_teacher_id', $academicClass->homeroom_teacher_id)" placeholder="Belum ditetapkan">
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected((string) old('homeroom_teacher_id', $academicClass->homeroom_teacher_id) === (string) $teacher->id)>{{ $teacher->name }}{{ $teacher->staffProfile?->employee_number ? ' · '.$teacher->staffProfile->employee_number : '' }}</option>
            @endforeach
        </x-select>
        <x-select name="status" label="Status" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif']" :selected="old('status', $academicClass->status ?? 'ACTIVE')" required />
    </div>
</section>
<div class="flex flex-wrap justify-end gap-3"><x-button type="submit" icon="save">Simpan kelas</x-button><x-button href="{{ $academicClass->exists ? route('admin.academic-classes.show', $academicClass) : route('admin.academic-classes.index') }}" variant="cancel" icon="x">Batal</x-button></div>
