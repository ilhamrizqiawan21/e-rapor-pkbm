<div class="space-y-5">
    <x-input name="title" label="Judul snippet" :value="old('title', $snippet->title)" :error="$errors->first('title')" placeholder="Contoh: Mampu memahami konsep bilangan" required />
    <x-textarea name="content" label="Isi deskripsi" rows="7" :value="old('content', $snippet->content)" :error="$errors->first('content')" placeholder="Tuliskan deskripsi capaian yang dapat digunakan kembali." hint="Isi dapat diedit lagi pada input nilai peserta didik setelah dimasukkan." required />

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
