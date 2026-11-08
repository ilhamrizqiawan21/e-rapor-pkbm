<div class="space-y-8">
    {{-- Bagian I: Data Pokok & Kependudukan --}}
    <div>
        <h4 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-2 mb-4">
            A. Identitas Pribadi & Kependudukan
        </h4>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-input name="student_number" label="NIPD / Nomor Induk Satuan" :value="old('student_number', $student->student_number)" :error="$errors->first('student_number')" required hint="Unik di dalam institusi." />
            <x-input name="national_student_number" label="NISN" :value="old('national_student_number', $student->national_student_number)" :error="$errors->first('national_student_number')" hint="Nomor Induk Siswa Nasional." />
            <x-input name="nik" label="NIK Peserta Didik" :value="old('nik', $student->nik)" :error="$errors->first('nik')" hint="16 digit sesuai KTP/KIA/KK." maxlength="20" />
            <x-input name="family_card_number" label="Nomor Kartu Keluarga (KK)" :value="old('family_card_number', $student->family_card_number)" :error="$errors->first('family_card_number')" hint="16 digit nomor KK." maxlength="20" />
            <x-input name="full_name" label="Nama Lengkap" :value="old('full_name', $student->full_name)" :error="$errors->first('full_name')" required autocomplete="name" hint="Sesuai akta lahir / ijazah sebelumnya." />
            <x-input name="nickname" label="Nama Panggilan" :value="old('nickname', $student->nickname)" :error="$errors->first('nickname')" />
            <x-select name="gender" label="Jenis Kelamin" :selected="old('gender', $student->gender)" :error="$errors->first('gender')" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" placeholder="Pilih jenis kelamin" required />
            <x-select name="status" label="Status Peserta Didik" :selected="old('status', $student->status ?? 'ACTIVE')" :error="$errors->first('status')" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'GRADUATED' => 'Lulus', 'TRANSFERRED' => 'Pindah']" required />
            <x-input name="birth_place" label="Tempat Lahir" :value="old('birth_place', $student->birth_place)" :error="$errors->first('birth_place')" />
            <x-input name="birth_date" type="date" label="Tanggal Lahir" :value="old('birth_date', $student->birth_date?->format('Y-m-d'))" :error="$errors->first('birth_date')" />
            <x-select name="religion" label="Agama" :selected="old('religion', $student->religion)" :error="$errors->first('religion')" :options="['Islam' => 'Islam', 'Kristen Protestan' => 'Kristen Protestan', 'Katolik' => 'Katolik', 'Hindu' => 'Hindu', 'Buddha' => 'Buddha', 'Khonghucu' => 'Khonghucu', 'Lainnya' => 'Lainnya']" placeholder="Pilih agama" />
            <x-input name="citizenship" label="Kewarganegaraan" :value="old('citizenship', $student->citizenship ?? 'WNI')" :error="$errors->first('citizenship')" />
            <x-input name="birth_order" type="number" min="1" max="99" label="Anak ke-" :value="old('birth_order', $student->birth_order)" :error="$errors->first('birth_order')" />
            <x-input name="siblings_count" type="number" min="0" max="99" label="Jumlah Saudara Kandung" :value="old('siblings_count', $student->siblings_count)" :error="$errors->first('siblings_count')" />
        </div>
    </div>

    {{-- Bagian II: Kontak & Tempat Tinggal --}}
    <div>
        <h4 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-2 mb-4">
            B. Kontak & Tempat Tinggal
        </h4>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-textarea name="address" label="Alamat Lengkap" :value="old('address', $student->address)" :error="$errors->first('address')" rows="3" hint="Jalan, RT/RW, Dusun, Kelurahan/Desa, Kecamatan, Kab/Kota." />
            </div>
            <x-input name="postal_code" label="Kode Pos" :value="old('postal_code', $student->postal_code)" :error="$errors->first('postal_code')" maxlength="10" />
            <x-input name="phone_number" label="Nomor Telepon / WhatsApp" :value="old('phone_number', $student->phone_number)" :error="$errors->first('phone_number')" hint="Kontak aktif siswa." />
            <x-select name="living_with" label="Tinggal Bersama" :selected="old('living_with', $student->living_with)" :error="$errors->first('living_with')" :options="['Orang Tua' => 'Orang Tua', 'Wali' => 'Wali', 'Sendiri/Kos' => 'Sendiri / Kos', 'Asrama' => 'Asrama / Pondok', 'Lainnya' => 'Lainnya']" placeholder="Pilih tempat tinggal" />
            <x-input name="transportation_mode" label="Moda Transportasi" :value="old('transportation_mode', $student->transportation_mode)" :error="$errors->first('transportation_mode')" hint="Contoh: Jalan Kaki, Sepeda Motor, Angkutan Umum." />
        </div>
    </div>

    {{-- Bagian III: Data Orang Tua Kandung & Wali --}}
    <div>
        <h4 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-2 mb-4">
            C. Data Orang Tua Kandung & Wali
        </h4>

        {{-- Ayah --}}
        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 mb-5">
            <h5 class="text-sm font-semibold text-slate-800 mb-3 flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-accent-500"></span>
                Data Ayah Kandung
            </h5>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="father_name" label="Nama Ayah" :value="old('father_name', $student->father_name)" :error="$errors->first('father_name')" />
                <x-input name="father_nik" label="NIK Ayah" :value="old('father_nik', $student->father_nik)" :error="$errors->first('father_nik')" maxlength="20" />
                <x-input name="father_birth_place" label="Tempat Lahir Ayah" :value="old('father_birth_place', $student->father_birth_place)" :error="$errors->first('father_birth_place')" />
                <x-input name="father_birth_date" type="date" label="Tanggal Lahir Ayah" :value="old('father_birth_date', $student->father_birth_date?->format('Y-m-d'))" :error="$errors->first('father_birth_date')" />
                <x-input name="father_education" label="Pendidikan Terakhir Ayah" :value="old('father_education', $student->father_education)" :error="$errors->first('father_education')" hint="SD/SMP/SMA/S1/dst." />
                <x-input name="father_occupation" label="Pekerjaan Ayah" :value="old('father_occupation', $student->father_occupation)" :error="$errors->first('father_occupation')" />
                <x-input name="father_income" label="Penghasilan Rata-rata" :value="old('father_income', $student->father_income)" :error="$errors->first('father_income')" hint="Contoh: < 1 Juta, 1-3 Juta, dll." />
                <x-input name="father_phone" label="Nomor Telepon / WA Ayah" :value="old('father_phone', $student->father_phone)" :error="$errors->first('father_phone')" />
            </div>
        </div>

        {{-- Ibu --}}
        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 mb-5">
            <h5 class="text-sm font-semibold text-slate-800 mb-3 flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-accent-500"></span>
                Data Ibu Kandung
            </h5>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="mother_name" label="Nama Ibu" :value="old('mother_name', $student->mother_name)" :error="$errors->first('mother_name')" />
                <x-input name="mother_nik" label="NIK Ibu" :value="old('mother_nik', $student->mother_nik)" :error="$errors->first('mother_nik')" maxlength="20" />
                <x-input name="mother_birth_place" label="Tempat Lahir Ibu" :value="old('mother_birth_place', $student->mother_birth_place)" :error="$errors->first('mother_birth_place')" />
                <x-input name="mother_birth_date" type="date" label="Tanggal Lahir Ibu" :value="old('mother_birth_date', $student->mother_birth_date?->format('Y-m-d'))" :error="$errors->first('mother_birth_date')" />
                <x-input name="mother_education" label="Pendidikan Terakhir Ibu" :value="old('mother_education', $student->mother_education)" :error="$errors->first('mother_education')" hint="SD/SMP/SMA/S1/dst." />
                <x-input name="mother_occupation" label="Pekerjaan Ibu" :value="old('mother_occupation', $student->mother_occupation)" :error="$errors->first('mother_occupation')" />
                <x-input name="mother_income" label="Penghasilan Rata-rata" :value="old('mother_income', $student->mother_income)" :error="$errors->first('mother_income')" />
                <x-input name="mother_phone" label="Nomor Telepon / WA Ibu" :value="old('mother_phone', $student->mother_phone)" :error="$errors->first('mother_phone')" />
            </div>
        </div>

        {{-- Wali --}}
        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
            <h5 class="text-sm font-semibold text-slate-800 mb-3 flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-slate-400"></span>
                Data Wali (Jika Tidak Tinggal Bersama Orang Tua)
            </h5>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="guardian_name" label="Nama Wali" :value="old('guardian_name', $student->guardian_name)" :error="$errors->first('guardian_name')" />
                <x-input name="guardian_relation" label="Hubungan Keluarga" :value="old('guardian_relation', $student->guardian_relation)" :error="$errors->first('guardian_relation')" hint="Paman, Kakek, Kakak, dll." />
                <x-input name="guardian_occupation" label="Pekerjaan Wali" :value="old('guardian_occupation', $student->guardian_occupation)" :error="$errors->first('guardian_occupation')" />
                <x-input name="guardian_phone" label="Nomor Telepon / WA Wali" :value="old('guardian_phone', $student->guardian_phone)" :error="$errors->first('guardian_phone')" />
            </div>
        </div>
    </div>

    {{-- Bagian IV: Riwayat Pendidikan Sebelumnya & Penerimaan --}}
    <div>
        <h4 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-2 mb-4">
            D. Riwayat Pendidikan & Penerimaan di PKBM
        </h4>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-input name="previous_school_name" label="Sekolah / Satuan Pendidikan Asal" :value="old('previous_school_name', $student->previous_school_name)" :error="$errors->first('previous_school_name')" hint="SD/MI atau SMP/MTs atau PKBM asal." />
            <x-input name="previous_diploma_number" label="Nomor Ijazah / SKL Asal" :value="old('previous_diploma_number', $student->previous_diploma_number)" :error="$errors->first('previous_diploma_number')" hint="Nomor ijazah jenjang sebelumnya." />
            <x-input name="entry_date" type="date" label="Tanggal Diterima Masuk di PKBM" :value="old('entry_date', $student->entry_date?->format('Y-m-d'))" :error="$errors->first('entry_date')" hint="Tanggal resmi mulai tercatat di PKBM." />
            <x-input name="graduation_certificate_number" label="Nomor Seri Ijazah PKBM (Saat Lulus)" :value="old('graduation_certificate_number', $student->graduation_certificate_number)" :error="$errors->first('graduation_certificate_number')" hint="Diisi setelah ijazah resmi diterbitkan dinas." />
        </div>
    </div>
</div>
