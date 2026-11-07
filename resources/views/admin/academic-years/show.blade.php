@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-6xl">
        @if(session('status'))<p role="status" class="mb-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</p>@endif
        <x-page-header eyebrow="Detail tahun pelajaran" title="{{ $academicYear->name }}" description="{{ $academicYear->start_date?->translatedFormat('d F Y') }} — {{ $academicYear->end_date?->translatedFormat('d F Y') }}" back-href="{{ route('admin.academic-years.index') }}" back-text="Kembali ke tahun pelajaran">
            <x-badge variant="{{ strtolower($academicYear->status->value) }}" dot>{{ $academicYear->status->label() }}</x-badge>
            @if(($actionState ?? null) && $actionState['semester'])
                @if($actionState['year_change'])
                    <x-button href="{{ route('admin.academic-years.transition', $academicYear) }}">{{ auth()->user()->hasRole('ADMIN') ? 'Siapkan tahun baru' : 'Kesiapan tahun baru' }}</x-button>
                @else
                    <x-button type="button" @click="$dispatch('open-modal', 'semester-start-{{ $academicYear->id }}')">{{ auth()->user()->hasRole('ADMIN') ? 'Mulai semester genap' : 'Kesiapan semester genap' }}</x-button>
                @endif
            @endif
            @can('update', $academicYear)<x-button href="#" @click.prevent="$dispatch('open-modal', 'academic-year-detail-edit')" variant="edit" icon="edit">Edit periode</x-button>@endcan
        </x-page-header>
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="flex flex-col gap-4 border-b border-slate-100 p-6 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-lg font-semibold text-slate-950">Semester</h3><p class="mt-1 text-sm text-slate-500">Kelola urutan, status, dan periode input akademik.</p></div>@can('create', [App\Models\Semester::class, $academicYear])<x-button size="sm" href="#" @click.prevent="$dispatch('open-modal', 'semester-create')" icon="add">Tambah semester</x-button>@endcan</div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-6 py-3">Urutan</th><th class="px-6 py-3">Semester</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($academicYear->semesters as $semester)<tr><td class="px-6 py-4 font-medium text-slate-700">{{ $semester->sequence }}</td><td class="px-6 py-4"><p class="font-semibold text-slate-900">{{ $semester->name }}</p><p class="text-xs text-slate-500">{{ $semester->code->value }}</p></td><td class="px-6 py-4"><x-badge variant="{{ strtolower($semester->status->value) }}" dot>{{ $semester->status->label() }}</x-badge></td><td class="px-6 py-4 text-right">@can('update', $semester)<x-button size="sm" variant="edit" icon="edit" href="#" @click.prevent="$dispatch('open-modal', 'semester-edit-{{ $semester->id }}')">Edit</x-button>@endcan</td></tr>@empty<tr><td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">Belum ada semester.</td></tr>@endforelse</tbody></table></div></section>

        @if(($actionState ?? null) && ! $actionState['year_change'] && $actionState['semester'])
            @include('admin.academic-years.partials.semester-start', ['academicYear' => $academicYear, 'state' => $actionState])
        @endif
        @can('update', $academicYear)
            <x-modal name="academic-year-detail-edit" title="Edit tahun pelajaran" max-width="4xl">
                @include('admin.academic-years.form', ['academicYear' => $academicYear, 'statusOptions' => App\Enums\AcademicYearStatus::cases(), 'modalName' => 'academic-year-detail-edit'])
            </x-modal>
        @endcan

        @can('create', [App\Models\Semester::class, $academicYear])
            <x-modal name="semester-create" title="Tambah semester" max-width="4xl">
                @include('admin.semesters.form', ['academicYear' => $academicYear, 'semester' => $modalSemester, 'codeOptions' => $semesterCodeOptions, 'statusOptions' => $semesterStatusOptions, 'modalName' => 'semester-create'])
            </x-modal>
        @endcan

        @foreach ($academicYear->semesters as $semester)
            @can('update', $semester)
                <x-modal name="semester-edit-{{ $semester->id }}" title="Edit semester" max-width="4xl">
                    @include('admin.semesters.form', ['academicYear' => $academicYear, 'semester' => $semester, 'codeOptions' => $semesterCodeOptions, 'statusOptions' => $semesterStatusOptions, 'modalName' => 'semester-edit-'.$semester->id])
                </x-modal>
            @endcan
        @endforeach
    </div>
@endsection
