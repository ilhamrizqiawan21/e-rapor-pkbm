@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Data inti / periode akademik" title="Tahun Pelajaran" description="Kelola periode akademik dan semester PKBM sebagai dasar kelas, nilai, dan rapor.">
            @can('create', App\Models\AcademicYear::class)<x-button href="#" icon="add" @click.prevent="$dispatch('open-modal', 'academic-year-create')">Tambah tahun pelajaran</x-button>@endcan
        </x-page-header>

        @if ($academicYears->isEmpty())
            @can('create', App\Models\AcademicYear::class)
                <x-empty-state title="Belum ada tahun pelajaran" description="Buat tahun pelajaran pertama untuk mulai mengelola semester dan kelas.">
                    <x-slot:actions><x-button href="#" @click.prevent="$dispatch('open-modal', 'academic-year-create')" size="sm" icon="add">Tambah tahun pelajaran</x-button></x-slot:actions>
                </x-empty-state>
            @else
                <x-empty-state title="Belum ada tahun pelajaran" description="Belum ada periode akademik pada institusi ini." />
            @endcan
        @else
            <section class="space-y-4" aria-labelledby="academic-year-list-heading">
                <div class="flex items-center justify-between gap-3"><div><h2 id="academic-year-list-heading" class="text-lg font-semibold text-slate-950">Daftar periode</h2><p class="mt-1 text-sm text-slate-500">Periode terbaru ditampilkan lebih dahulu.</p></div><span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $academicYears->count() }} periode</span></div>
                @foreach ($academicYears as $academicYear)
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <h3 class="text-xl font-semibold text-slate-950">{{ $academicYear->name }}</h3>
                                    <x-badge variant="{{ strtolower($academicYear->status->value) }}" dot>{{ $academicYear->status->label() }}</x-badge>
                                </div>
                                <p class="mt-2 text-sm text-slate-500">{{ $academicYear->start_date?->translatedFormat('d F Y') }} — {{ $academicYear->end_date?->translatedFormat('d F Y') }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if(isset($periodActions[$academicYear->id]) && $periodActions[$academicYear->id]['semester'])
                                    @if($periodActions[$academicYear->id]['year_change'])
                                        <x-button size="sm" href="{{ route('admin.academic-years.transition', $academicYear) }}">{{ auth()->user()->hasRole('ADMIN') ? 'Siapkan tahun baru' : 'Kesiapan tahun baru' }}</x-button>
                                    @else
                                        <x-button size="sm" type="button" @click="$dispatch('open-modal', 'semester-start-{{ $academicYear->id }}')">{{ auth()->user()->hasRole('ADMIN') ? 'Mulai semester genap' : 'Kesiapan semester genap' }}</x-button>
                                    @endif
                                @endif
                                <x-button size="sm" variant="detail" icon="eye" href="{{ route('admin.academic-years.show', $academicYear) }}">Detail semester</x-button>
                                @can('update', $academicYear)
                                    <x-button size="sm" href="#" @click.prevent="$dispatch('open-modal', 'academic-year-edit-{{ $academicYear->id }}')" variant="edit" icon="edit">Edit</x-button>
                                @endcan
                            </div>
                        </div>
                        <div class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Semester</p><p class="mt-1 font-semibold text-slate-900">{{ $academicYear->semesters_count }} semester</p></div>
                            <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kelas</p><p class="mt-1 font-semibold text-slate-900">{{ $academicYear->classes_count }} kelas</p></div>
                        </div>
                    </section>
                @endforeach
            </section>
        @endif

        @can('create', App\Models\AcademicYear::class)
            <x-modal name="academic-year-create" title="Tambah tahun pelajaran" max-width="4xl">
                @include('admin.academic-years.form', ['academicYear' => $modalAcademicYear, 'statusOptions' => $statusOptions, 'modalName' => 'academic-year-create'])
            </x-modal>
        @endcan

        @foreach ($academicYears as $academicYear)
            @if(isset($periodActions[$academicYear->id]) && ! $periodActions[$academicYear->id]['year_change'] && $periodActions[$academicYear->id]['semester'])
                @include('admin.academic-years.partials.semester-start', ['academicYear' => $academicYear, 'state' => $periodActions[$academicYear->id]])
            @endif
            @can('update', $academicYear)
                <x-modal name="academic-year-edit-{{ $academicYear->id }}" title="Edit tahun pelajaran" max-width="4xl">
                    @include('admin.academic-years.form', ['academicYear' => $academicYear, 'statusOptions' => $statusOptions, 'modalName' => 'academic-year-edit-'.$academicYear->id])
                </x-modal>
            @endcan
        @endforeach
    </div>
@endsection
