@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Data inti / kelas & rombel" title="Kelas / Rombel" description="Atur kelas, wali kelas, peserta didik, dan penugasan mapel sesuai periode akademik.">
            @can('create', App\Models\SubjectAssignment::class)<x-button href="{{ route('admin.academic-classes.subject-assignments.bulk-create') }}" variant="secondary">Mapping beberapa kelas</x-button><x-button href="{{ route('admin.academic-classes.subject-assignments.bulk-assign-teacher.create') }}" variant="secondary">Bulk assign guru</x-button>@endcan
            @can('create', App\Models\AcademicClass::class)<x-button href="{{ route('admin.academic-classes.create') }}" icon="add">Tambah kelas</x-button>@endcan
        </x-page-header>

        @if (session('status'))<div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status"><svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" /></svg><span>{{ session('status') }}</span></div>@endif

        @if ($classes->isEmpty())
            @can('create', App\Models\AcademicClass::class)
                <x-empty-state title="Belum ada kelas" description="Kelas yang dapat Anda akses akan tampil di halaman ini.">
                    <x-slot:actions><x-button href="{{ route('admin.academic-classes.create') }}" size="sm">Buat kelas pertama</x-button></x-slot:actions>
                </x-empty-state>
            @else
                <x-empty-state title="Belum ada kelas" description="Belum ada kelas yang dapat Anda akses pada periode ini." />
            @endcan
        @else
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="class-directory-heading">
                <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:px-6"><div><h2 id="class-directory-heading" class="text-lg font-semibold text-slate-950">Direktori kelas</h2><p class="mt-1 text-sm text-slate-500">Pantau isi kelas, penugasan mapel, dan wali kelas dari satu tempat.</p></div><span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $classes->total() }} kelas</span></div>
                <div class="hidden overflow-x-auto md:block"><table class="min-w-full divide-y divide-slate-200 text-sm"><caption class="sr-only">Daftar kelas dan rombel</caption><thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><x-table.th>Kelas</x-table.th><x-table.th>Periode / program</x-table.th><x-table.th>Wali kelas</x-table.th><x-table.th align="center">Siswa</x-table.th><x-table.th align="center">Mapel</x-table.th><x-table.th>Status</x-table.th><x-table.th align="right">Aksi</x-table.th></tr></thead><tbody class="divide-y divide-slate-100">
                    @foreach ($classes as $academicClass)
                        <tr class="align-top transition-colors hover:bg-slate-50"><x-table.td><a class="font-semibold text-brand-900 hover:text-brand-700 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500" href="{{ route('admin.academic-classes.show', $academicClass) }}">{{ $academicClass->name }}</a><p class="mt-1 text-xs text-slate-500">{{ $academicClass->code }} · Tingkat {{ $academicClass->level->number }}</p></x-table.td><x-table.td><p>{{ $academicClass->academicYear->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $academicClass->program->name }}</p></x-table.td><x-table.td>{{ $academicClass->homeroomTeacher?->name ?? 'Belum ditetapkan' }}</x-table.td><x-table.td align="center">{{ $academicClass->active_enrollments_count }}</x-table.td><x-table.td align="center">{{ $academicClass->active_subject_assignments_count }}</x-table.td><x-table.td><x-badge :variant="$academicClass->status" dot>{{ $academicClass->status === 'ACTIVE' ? 'Aktif' : 'Nonaktif' }}</x-badge></x-table.td><x-table.td align="right"><x-button size="sm" variant="secondary" href="{{ route('admin.academic-classes.show', $academicClass) }}">Lihat detail</x-button></x-table.td></tr>
                    @endforeach
                </tbody></table></div>
                <div class="space-y-3 p-4 md:hidden">@foreach ($classes as $academicClass)<article class="rounded-2xl border border-slate-200 p-4" data-responsive-card><div class="flex items-start justify-between gap-3"><div><a href="{{ route('admin.academic-classes.show', $academicClass) }}" class="font-semibold text-brand-900">{{ $academicClass->name }}</a><p class="mt-1 text-xs text-slate-500">{{ $academicClass->code }} · {{ $academicClass->program->name }}</p></div><x-badge :variant="$academicClass->status" dot>{{ $academicClass->status === 'ACTIVE' ? 'Aktif' : 'Nonaktif' }}</x-badge></div><dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-xs text-slate-500">Periode</dt><dd class="mt-1 font-medium text-slate-800">{{ $academicClass->academicYear->name }}</dd></div><div><dt class="text-xs text-slate-500">Wali kelas</dt><dd class="mt-1 font-medium text-slate-800">{{ $academicClass->homeroomTeacher?->name ?? 'Belum ditetapkan' }}</dd></div><div><dt class="text-xs text-slate-500">Siswa aktif</dt><dd class="mt-1 font-medium text-slate-800">{{ $academicClass->active_enrollments_count }}</dd></div><div><dt class="text-xs text-slate-500">Mapel aktif</dt><dd class="mt-1 font-medium text-slate-800">{{ $academicClass->active_subject_assignments_count }}</dd></div></dl><div class="mt-4"><x-button size="sm" variant="secondary" href="{{ route('admin.academic-classes.show', $academicClass) }}">Lihat detail</x-button></div></article>@endforeach</div>
                <div class="border-t border-slate-100 px-5 sm:px-6"><x-pagination :paginator="$classes" /></div>
            </section>
        @endif
    </div>
@endsection
