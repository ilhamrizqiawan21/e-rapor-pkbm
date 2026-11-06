<div class="grid gap-5 sm:grid-cols-2">
    <x-input name="name" label="Nama lengkap" :value="old('name', $teacher->name)" :error="$errors->first('name')" required autocomplete="name" />
    <x-input name="username" label="Username" :value="old('username', $teacher->username)" :error="$errors->first('username')" required autocomplete="username" hint="Huruf kecil, angka, titik, garis bawah, dan tanda hubung." />
    <x-input name="email" type="email" label="Email" :value="old('email', $teacher->email)" :error="$errors->first('email')" autocomplete="email" />
    <x-input name="employee_number" label="NIP / NIY" :value="old('employee_number', $teacher->staffProfile?->employee_number)" :error="$errors->first('employee_number')" />
    <x-input name="phone" label="Telepon" :value="old('phone', $teacher->staffProfile?->phone)" :error="$errors->first('phone')" autocomplete="tel" />
    <x-input name="title" label="Jabatan" :value="old('title', $teacher->staffProfile?->title)" :error="$errors->first('title')" placeholder="Contoh: Tutor Bahasa Indonesia" />
    <x-select name="status" label="Status akun" :selected="old('status', $teacher->status?->value ?? 'ACTIVE')" :error="$errors->first('status')" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif']" required />
</div>

@if (! $teacher->exists)
    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <x-input name="password" type="password" label="Password awal" :error="$errors->first('password')" autocomplete="new-password" required hint="Minimal 8 karakter, huruf besar-kecil, dan angka." />
        <x-input name="password_confirmation" type="password" label="Ulangi password awal" :error="$errors->first('password_confirmation')" autocomplete="new-password" required />
    </div>
@endif
