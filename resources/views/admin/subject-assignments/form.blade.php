@csrf
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    @if ($subjects->isEmpty())
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">Belum ada mata pelajaran aktif. Tambahkan atau aktifkan mapel pada modul Master Mata Pelajaran terlebih dahulu.</div>
    @endif
    <div class="grid gap-5 sm:grid-cols-2">
        <x-select name="subject_id" label="Mata pelajaran" :selected="old('subject_id', $assignment->subject_id)" placeholder="Pilih mata pelajaran" required>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}" @selected((string) old('subject_id', $assignment->subject_id) === (string) $subject->id)>{{ $subject->name }} · {{ $subject->code }}{{ $subject->active ? '' : ' · nonaktif' }}</option>
            @endforeach
        </x-select>
        <x-select name="teacher_id" label="Guru pengampu" :selected="old('teacher_id', $assignment->teacher_id)" placeholder="Pilih guru" required>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected((string) old('teacher_id', $assignment->teacher_id) === (string) $teacher->id)>{{ $teacher->name }}{{ $teacher->staffProfile?->employee_number ? ' · '.$teacher->staffProfile->employee_number : '' }}</option>
            @endforeach
        </x-select>
        <x-input name="minimum_score" type="number" min="0" max="100" label="KKM / nilai minimum" :value="old('minimum_score', $assignment->minimum_score ?? 70)" required />
        <x-input name="display_order" type="number" min="1" max="9999" label="Urutan tampil" :value="old('display_order', $assignment->display_order ?? 1)" hint="Urutan mapel pada daftar dan rapor." required />
    </div>
    <label class="mt-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
        <input type="hidden" name="active" value="0">
        <input class="mt-0.5 rounded border-slate-300 text-brand-700 focus:ring-brand-600" type="checkbox" name="active" value="1" @checked(old('active', $assignment->active ?? true))>
        <span><span class="font-semibold text-slate-900">Penugasan aktif</span><span class="mt-1 block text-xs text-slate-500">Penugasan nonaktif tidak digunakan untuk pengisian nilai baru.</span></span>
    </label>
</section>
<div class="flex flex-wrap justify-end gap-3"><x-button type="submit" :disabled="$subjects->isEmpty()" icon="save">Simpan penugasan</x-button><x-button href="{{ route('admin.academic-classes.subject-assignments.index', $academicClass) }}" variant="cancel" icon="x">Batal</x-button></div>
