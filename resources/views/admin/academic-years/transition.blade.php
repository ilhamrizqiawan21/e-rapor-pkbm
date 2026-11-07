@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <x-page-header eyebrow="Tahun pelajaran" :title="$state['year_change'] ? 'Siapkan tahun baru' : 'Mulai semester genap'" description="Periksa kesiapan periode dan pertahankan arsip tahun sebelumnya." back-href="{{ route('admin.academic-years.index') }}" back-text="Kembali ke tahun pelajaran" />
    @if(session('status'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</p>@endif
    @if(! $state['semester'])
        <x-empty-state title="Tidak ada semester aktif" description="Periode ini sudah ditutup atau belum memiliki semester aktif. Riwayat sebelumnya tetap tersimpan." />
    @elseif(! $state['year_change'])
        <div x-data x-init="$nextTick(() => $dispatch('open-modal', 'semester-start-{{ $academicYear->id }}'))">
            <x-button type="button" @click="$dispatch('open-modal', 'semester-start-{{ $academicYear->id }}')">Periksa kesiapan semester</x-button>
            @include('admin.academic-years.partials.semester-start', ['academicYear' => $academicYear, 'state' => $state])
        </div>
    @else
        <nav aria-label="Langkah persiapan" class="grid grid-cols-3 gap-2">
            @foreach([1 => 'Tahun tujuan', 2 => 'Kelas & siswa', 3 => 'Ringkasan'] as $number => $label)
                <a href="{{ route('admin.academic-years.transition', [$academicYear, 'step' => $number]) }}" @if($step === $number) aria-current="step" @endif class="rounded-xl border px-3 py-3 text-sm font-semibold {{ $step === $number ? 'border-brand-800 bg-brand-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}"><span class="mr-1">{{ $number }}.</span> {{ $label }}</a>
            @endforeach
        </nav>
        @if($step === 1)
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">Tahun pelajaran tujuan</h2>
                <p class="mt-1 text-sm text-slate-500">Ganjil dan Genap dibuat otomatis sebagai Draf. Periode {{ $academicYear->name }} tetap aktif selama persiapan.</p>
                @if($canManage)
                    <form method="POST" action="{{ route('admin.academic-years.transition.prepare', $academicYear) }}" class="mt-5 space-y-5">@csrf
                        <input type="hidden" name="revision" value="{{ $preparation?->revision ?? 0 }}">
                        <div class="grid gap-5 sm:grid-cols-3">
                            <x-input name="name" label="Tahun pelajaran" :value="old('name', $preparation?->targetYear?->name ?? $suggested['name'])" required />
                            <x-input name="start_date" type="date" label="Tanggal mulai" :value="old('start_date', $preparation?->targetYear?->start_date?->toDateString() ?? $suggested['start_date'])" required />
                            <x-input name="end_date" type="date" label="Tanggal selesai" :value="old('end_date', $preparation?->targetYear?->end_date?->toDateString() ?? $suggested['end_date'])" required />
                        </div>
                        <p class="text-xs text-slate-500">Jika nama yang sama sudah tersedia sebagai Draf, tahun tersebut digunakan untuk persiapan ini.</p>
                        <div class="flex justify-end"><x-button type="submit" icon="save">Simpan & lanjutkan</x-button></div>
                    </form>
                @else<p class="mt-4 text-sm text-slate-700">{{ $preparation?->targetYear?->name ?? 'Admin belum menyiapkan tahun tujuan.' }}</p>@endif
            </section>
        @elseif(! $preparation)
            <x-empty-state title="Tahun tujuan belum disiapkan" description="Mulai dari langkah Tahun tujuan terlebih dahulu."><x-slot:actions><x-button href="{{ route('admin.academic-years.transition', [$academicYear, 'step' => 1]) }}">Ke tahun tujuan</x-button></x-slot:actions></x-empty-state>
        @elseif($step === 2)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"><div><h2 class="font-semibold text-slate-950">Kelas tahun {{ $preparation->targetYear->name }}</h2><p class="mt-1 text-sm text-slate-500">Tinjau wali kelas, mapel, guru, dan KKM. Penilaian lama tidak disalin.</p></div>
                    @if($canManage)<form method="POST" action="{{ route('admin.academic-years.transition.copy', $academicYear) }}">@csrf<x-button type="submit" variant="secondary" icon="copy">Salin struktur kelas</x-button></form>@endif
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse($state['classes'] as $targetClass)<div class="rounded-xl border border-slate-200 p-4"><p class="text-sm font-semibold">{{ $targetClass->name }}</p><p class="mt-1 text-xs text-slate-500">Wali kelas: {{ $targetClass->homeroomTeacher?->name ?? 'Belum ditentukan' }}</p><p class="mt-1 text-xs text-slate-500">{{ $targetClass->subjectAssignments->where('active', true)->count() }} mata pelajaran</p><a class="mt-3 inline-block text-xs font-semibold text-blue-700 underline" href="{{ route('admin.academic-classes.show', $targetClass) }}" target="_blank" rel="noopener">Periksa penugasan ↗</a></div>@empty<p class="text-sm text-slate-500">Belum ada kelas tujuan. Salin struktur atau tambahkan kelas melalui Kelas / rombel.</p>@endforelse
                </div>
                @if($canManage)<a class="mt-4 inline-block text-sm font-semibold text-blue-700 underline" href="{{ route('admin.academic-classes.index') }}" target="_blank" rel="noopener">Kelola kelas dan penugasan ↗</a>@endif
            </section>
            <form method="POST" action="{{ route('admin.academic-years.transition.placements', $academicYear) }}" class="space-y-5">@csrf @method('PUT')
                <input type="hidden" name="revision" value="{{ $preparation->revision }}">
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 p-5"><h2 class="font-semibold text-slate-950">Penempatan siswa</h2><p class="mt-1 text-sm text-slate-500">Keputusan dari wali kelas. Pilih kelas tujuan setiap siswa sesuai keputusan tersebut.</p></div>
                    <div class="divide-y divide-slate-100">
                        @forelse($state['rows'] as $row)
                            @php($enrollment = $row['enrollment'])
                            @php($graduated = $row['decision']?->decision === 'GRADUATE')
                            @php($finalLevel = (int)$enrollment->academicClass->level->number === 12)
                            @php($classSelectDisabled = ! $canManage ? 'true' : ($graduated ? "continuation !== 'CONTINUE'" : 'false'))
                            <div class="grid gap-4 p-5 md:grid-cols-2" x-data="{ continuation: @js(old('rows.'.$enrollment->id.'.continuation', $row['placement']?->continuation ?? '')) }">
                                <div><p class="font-semibold text-slate-950">{{ $enrollment->student->full_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $enrollment->academicClass->name }} · {{ $enrollment->student->student_number }}</p><p class="mt-2 text-sm {{ $row['decision'] ? 'text-brand-800' : 'text-amber-700' }}">{{ $row['decision']?->label_snapshot ?? 'Menunggu keputusan wali kelas' }}</p></div>
                                <div class="space-y-3">
                                    @if(! $row['decision'])<p class="text-sm text-slate-500">Wali kelas perlu mengisi atau mengonfirmasi keputusan pada penempatan ini.</p>
                                    @elseif($graduated && $finalLevel)<p class="text-sm text-slate-600">Lulus Paket C — selesai di PKBM.</p>
                                    @else
                                        @if($graduated)<x-select name="rows[{{ $enrollment->id }}][continuation]" label="Kelanjutan setelah lulus" x-model="continuation" :disabled="! $canManage"><option value="">Pilih kelanjutan</option><option value="CONTINUE">Lanjut paket berikutnya</option><option value="FINISH">Selesai di PKBM</option></x-select>@endif
                                        <div @if($graduated) x-show="continuation === 'CONTINUE'" @endif>
                                            <x-select name="rows[{{ $enrollment->id }}][class_id]" label="Kelas tujuan {{ $enrollment->student->full_name }}" :disabled="! $canManage" x-bind:disabled="{{ $classSelectDisabled }}"><option value="">Pilih kelas tingkat {{ $row['expected_level'] }}</option>@foreach($state['classes']->filter(fn ($c) => (int)$c->level->number === $row['expected_level']) as $targetClass)<option value="{{ $targetClass->id }}" @selected((string)old('rows.'.$enrollment->id.'.class_id', $row['placement']?->class_id) === (string)$targetClass->id)>{{ $targetClass->name }}</option>@endforeach</x-select>
                                        </div>
                                    @endif
                                    @if($row['destination_error'])<p class="text-xs text-amber-800">{{ $row['destination_error'] }}</p>@endif
                                </div>
                            </div>
                        @empty<p class="p-5 text-sm text-slate-500">Tidak ada siswa aktif.</p>@endforelse
                    </div>
                </section>
                @if($canManage)<label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700"><input type="checkbox" name="configuration_reviewed" value="1" @checked(old('configuration_reviewed', $preparation->configuration_reviewed)) class="mt-1 rounded border-slate-300 text-brand-700">Saya sudah meninjau kelas, wali kelas, guru, mata pelajaran, dan KKM tahun tujuan.</label><div class="flex flex-wrap justify-end gap-3"><x-button type="submit" name="intent" value="draft" variant="secondary" icon="save">Simpan draf</x-button><x-button type="submit" name="intent" value="review" icon="eye">Simpan & periksa ringkasan</x-button></div>@endif
            </form>
            @if(! $canManage)<div class="flex justify-end"><x-button href="{{ route('admin.academic-years.transition', [$academicYear, 'step' => 3]) }}">Lihat ringkasan</x-button></div>@endif
        @else
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach(['PROMOTE'=>'Naik kelas','REPEAT'=>'Tetap tingkat','GRADUATE'=>'Lulus paket','NOT_GRADUATED'=>'Belum lulus'] as $code=>$label)<div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ $state['counts'][$code] }}</p></div>@endforeach
            </div>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold text-slate-950">{{ $academicYear->name }} → {{ $preparation->targetYear->name }}</h2><p class="mt-2 text-sm text-slate-500">Tahun asal akan ditutup. Arsip rapor tetap tersimpan; penilaian tahun tujuan dimulai kosong.</p>
                @if($state['blockers'])<ul class="mt-4 space-y-2 rounded-xl bg-amber-50 p-4 text-sm text-amber-900">@foreach($state['blockers'] as $blocker)<li>{{ $blocker }}</li>@endforeach</ul>@else<p class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">Semua persyaratan lengkap. Tahun baru siap dimulai.</p>@endif
                <div class="mt-5 space-y-3">@foreach($state['groups'] as $group)<div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4"><div><p class="text-sm font-semibold">{{ $group['name'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $group['missing_reports'] }} rapor dan {{ $group['missing_decisions'] }} keputusan belum lengkap</p></div><div class="flex gap-4 text-sm"><a class="font-semibold text-blue-700 underline" href="{{ route('admin.report-cards.index', ['academic_year_id'=>$academicYear->id,'semester_id'=>$state['semester']->id,'class_id'=>$group['class_id']]) }}">Periksa rapor</a><a class="font-semibold text-blue-700 underline" href="{{ route('academic-decisions.index', ['class_id'=>$group['class_id']]) }}">Lihat keputusan</a></div></div>@endforeach</div>
            </section>
            @if($canManage)
                @php($activationBlocked = ! $state['ready'] || $preparation->review_hash !== $state['fingerprint'])
                @if($preparation->review_hash !== $state['fingerprint'])<form method="POST" action="{{ route('admin.academic-years.transition.preview', $academicYear) }}">@csrf<x-button type="submit" variant="secondary" icon="eye">Perbarui ringkasan</x-button></form>@endif
                <form method="POST" action="{{ route('admin.academic-years.transition.store', $academicYear) }}" class="space-y-4" x-data="{ saving: false }" @submit="saving = true">@csrf<input type="hidden" name="fingerprint" value="{{ $state['fingerprint'] }}">
                    <label class="flex items-start gap-3 text-sm text-slate-700"><input type="checkbox" name="confirm" value="1" required @disabled(! $state['ready'] || $preparation->review_hash !== $state['fingerprint']) class="mt-1 rounded border-slate-300 text-brand-700">Saya sudah memeriksa ringkasan dan akan memulai tahun pelajaran baru.</label>
                    <div class="flex justify-end"><x-button type="submit" icon="save" :disabled="$activationBlocked" x-bind:disabled="saving || {{ $activationBlocked ? 'true' : 'false' }}">Mulai tahun pelajaran baru</x-button></div>
                </form>
            @endif
        @endif
    @endif
</div>
@endsection
