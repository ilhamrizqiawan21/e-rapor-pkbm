<div class="grid gap-5 sm:grid-cols-2">
    <x-input name="student_number" label="NIPD / nomor induk" :value="old('student_number', $student->student_number)" :error="$errors->first('student_number')" required hint="Unik di dalam institusi." />
    <x-input name="national_student_number" label="NISN" :value="old('national_student_number', $student->national_student_number)" :error="$errors->first('national_student_number')" hint="Opsional, tetapi harus unik jika diisi." />
    <x-input name="full_name" label="Nama lengkap" :value="old('full_name', $student->full_name)" :error="$errors->first('full_name')" required autocomplete="name" />
    <x-select name="gender" label="Jenis kelamin" :selected="old('gender', $student->gender)" :error="$errors->first('gender')" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" placeholder="Pilih jenis kelamin" required />
    <x-input name="birth_place" label="Tempat lahir" :value="old('birth_place', $student->birth_place)" :error="$errors->first('birth_place')" />
    <x-input name="birth_date" type="date" label="Tanggal lahir" :value="old('birth_date', $student->birth_date?->format('Y-m-d'))" :error="$errors->first('birth_date')" />
    <x-input name="guardian_name" label="Orang tua / wali" :value="old('guardian_name', $student->guardian_name)" :error="$errors->first('guardian_name')" />
    <x-select name="status" label="Status peserta didik" :selected="old('status', $student->status ?? 'ACTIVE')" :error="$errors->first('status')" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'GRADUATED' => 'Lulus', 'TRANSFERRED' => 'Pindah']" required />
</div>
<div class="mt-5">
    <x-textarea name="address" label="Alamat" :value="old('address', $student->address)" :error="$errors->first('address')" rows="4" />
</div>
