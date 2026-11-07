<div class="space-y-5">
    <x-input name="title" label="Judul snippet" :value="old('title', $snippet->title)" :error="$errors->first('title')" placeholder="Contoh: Mampu memahami konsep bilangan" required />
    <x-textarea name="material" label="Materi / capaian yang dinilai" rows="4" :value="old('material', $snippet->material)" :error="$errors->first('material')" placeholder="Contoh: memahami pengertian sifat terpuji" hint="Materi ini akan dimasukkan ke kalimat sesuai predikat nilai." />

    <section class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4 sm:p-5" aria-labelledby="predicate-template-heading">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-900 text-white" aria-hidden="true">Aa</span>
            <div>
                <h2 id="predicate-template-heading" class="font-semibold text-brand-950">Template berdasarkan predikat</h2>
                <p class="mt-1 text-sm leading-5 text-brand-800">Gunakan penanda <code class="rounded bg-white px-1.5 py-0.5 font-semibold">{material}</code> untuk menyisipkan materi. Contoh hasil: “Siswa sangat baik dalam memahami pengertian sifat terpuji.”</p>
            </div>
        </div>
        <div class="mt-5 space-y-4">
            @foreach ($predicateRanges as $range)
                @php($existingTemplate = $snippet->templates?->firstWhere('predicate_code', strtoupper($range->code))?->template)
                @php($templateValue = old('templates.'.strtoupper($range->code), $existingTemplate ?: ($templateDefaults[strtoupper($range->code)] ?? 'Siswa '.strtolower($range->label).' dalam {material}.')))
                <x-textarea name="templates[{{ strtoupper($range->code) }}]" label="{{ $range->code }} · {{ $range->label }} ({{ $range->minimum_score }}–{{ $range->maximum_score }})" rows="2" :value="$templateValue" :error="$errors->first('templates.'.strtoupper($range->code))" placeholder="Siswa {{ strtolower($range->label) }} dalam {material}." required />
            @endforeach
        </div>
    </section>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-select name="subject_id" label="Mata pelajaran (opsional)" :selected="old('subject_id', $snippet->subject_id)" :options="$subjects->pluck('name', 'id')->toArray()" placeholder="Semua mata pelajaran" :error="$errors->first('subject_id')" hint="Kosongkan jika kalimat dapat dipakai lintas mapel." />
        <x-select name="active" label="Status" :selected="old('active', $snippet->active === null ? 1 : (int) $snippet->active)" :options="['1' => 'Aktif', '0' => 'Nonaktif']" :error="$errors->first('active')" required />
    </div>

    @if (auth()->user()->hasRole('ADMIN'))
        <div class="grid gap-5 sm:grid-cols-2">
            <x-select name="visibility" label="Visibilitas" :selected="old('visibility', $snippet->visibility?->value ?? 'SHARED')" :options="['SHARED' => 'Dibagikan']" :error="$errors->first('visibility')" hint="Snippet dibagikan dapat digunakan guru dalam institusi." required />
            <x-select name="owner_user_id" label="Guru pemilik (opsional)" :selected="old('owner_user_id', $snippet->owner_user_id)" :options="$teachers->pluck('name', 'id')->toArray()" placeholder="Tidak ditentukan" :error="$errors->first('owner_user_id')" hint="Untuk penanda asal snippet; pengelolaan tetap oleh Admin." />
        </div>
    @else
        <input type="hidden" name="visibility" value="PRIVATE">
        <div class="rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-900">
            Snippet ini akan disimpan sebagai <strong>pribadi</strong> dan hanya dapat dikelola serta digunakan oleh Anda.
        </div>
    @endif
</div>
