<section aria-labelledby="teacher-identity-heading">
    <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-800"><x-nav-icon name="profile" /></span><div><h3 id="teacher-identity-heading" class="font-semibold text-slate-950">Identitas dan kontak</h3><p class="mt-1 text-sm leading-5 text-slate-500">Data ini membantu Admin dan Kepsek mengenali Guru saat mengatur penugasan.</p></div></div>
    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <x-input name="name" label="Nama lengkap" :value="old('name', $teacher->name)" :error="$errors->first('name')" required autocomplete="name" />
        <x-input name="employee_number" label="NIP / NIY" :value="old('employee_number', $teacher->staffProfile?->employee_number)" :error="$errors->first('employee_number')" hint="Opsional, tetapi disarankan untuk rapor dan dokumen resmi." />
        <x-input name="title" label="Jabatan" :value="old('title', $teacher->staffProfile?->title)" :error="$errors->first('title')" placeholder="Contoh: Tutor Bahasa Indonesia" />
        <x-input name="phone" label="Telepon" :value="old('phone', $teacher->staffProfile?->phone)" :error="$errors->first('phone')" autocomplete="tel" />
    </div>
</section>

<section aria-labelledby="teacher-access-heading" class="border-t border-slate-100 pt-6">
    <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-800"><x-nav-icon name="password" /></span><div><h3 id="teacher-access-heading" class="font-semibold text-slate-950">Akses aplikasi</h3><p class="mt-1 text-sm leading-5 text-slate-500">Gunakan username yang mudah diingat. Status nonaktif akan memblokir login.</p></div></div>
    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <x-input name="username" label="Username" :value="old('username', $teacher->username)" :error="$errors->first('username')" required autocomplete="username" hint="Huruf kecil, angka, titik, garis bawah, dan tanda hubung." />
        <x-input name="email" type="email" label="Email" :value="old('email', $teacher->email)" :error="$errors->first('email')" autocomplete="email" />
        <x-select name="status" label="Status akun" :selected="old('status', $teacher->status?->value ?? 'ACTIVE')" :error="$errors->first('status')" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif']" required />
    </div>
</section>

@if (! $teacher->exists)
    <section aria-labelledby="teacher-password-heading" class="border-t border-slate-100 pt-6">
        <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-800"><x-nav-icon name="password" /></span><div><h3 id="teacher-password-heading" class="font-semibold text-slate-950">Password awal</h3><p class="mt-1 text-sm leading-5 text-slate-500">Bagikan password awal secara aman dan minta Guru menggantinya setelah login.</p></div></div>
        <div class="mt-5 grid gap-5 sm:grid-cols-2"><x-input name="password" type="password" label="Password awal" :error="$errors->first('password')" autocomplete="new-password" required hint="Minimal 8 karakter, huruf besar-kecil, dan angka." /><x-input name="password_confirmation" type="password" label="Ulangi password awal" :error="$errors->first('password_confirmation')" autocomplete="new-password" required /></div>
    </section>
@endif
