@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Data inti / program & tingkat" title="Program pendidikan" description="Kelola Paket A, Paket B, Paket C, dan program custom PKBM.">
            @can('create', App\Models\Program::class)<x-button href="{{ route('admin.programs.create') }}" icon="add">Tambah program</x-button>@endcan
        </x-page-header>

        @if (session('status'))<div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status"><svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" /></svg><span>{{ session('status') }}</span></div>@endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="program-directory-heading">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:px-6"><div><h2 id="program-directory-heading" class="text-lg font-semibold text-slate-950">Direktori program</h2><p class="mt-1 text-sm text-slate-500">Setiap program memiliki struktur tingkat 1 sampai 12 yang dapat dikelola terpisah.</p></div><span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $programs->total() }} program</span></div>
            <div class="hidden overflow-x-auto md:block"><table class="min-w-full divide-y divide-slate-200 text-sm"><caption class="sr-only">Daftar program pendidikan</caption><thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><x-table.th>Kode</x-table.th><x-table.th>Nama program</x-table.th><x-table.th>Tingkat</x-table.th><x-table.th align="right">Aksi</x-table.th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($programs as $program)
                    <tr class="align-top transition-colors hover:bg-slate-50"><x-table.td><span class="font-semibold text-slate-900">{{ $program->code }}</span></x-table.td><x-table.td><a href="{{ route('admin.programs.show', $program) }}" class="font-semibold text-brand-900 hover:text-brand-700 focus:outline-hidden focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500">{{ $program->name }}</a></x-table.td><x-table.td><span class="font-medium text-slate-800">{{ $program->levels_count }} tingkat</span><p class="mt-1 text-xs text-slate-500">Struktur program</p></x-table.td><x-table.td align="right"><div class="flex flex-wrap justify-end gap-2"><x-button href="{{ route('admin.programs.show', $program) }}" variant="secondary" size="sm" icon="eye">Kelola tingkat</x-button>@can('update', $program)<x-button href="{{ route('admin.programs.edit', $program) }}" variant="edit" size="sm" icon="edit">Edit</x-button>@endcan</div></x-table.td></tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-12">
                        @can('create', App\Models\Program::class)
                            <x-empty-state title="Belum ada program" description="Tambahkan program atau jalankan seed Paket A–C untuk mulai mengatur tingkat.">
                                <x-slot:actions><x-button href="{{ route('admin.programs.create') }}" size="sm" icon="add">Tambah program</x-button></x-slot:actions>
                            </x-empty-state>
                        @else
                            <x-empty-state title="Belum ada program" description="Belum ada program pendidikan pada institusi ini." />
                        @endcan
                    </td></tr>
                @endforelse
            </tbody></table></div>
            <div class="space-y-3 p-4 md:hidden">@forelse ($programs as $program)<article class="rounded-2xl border border-slate-200 p-4" data-responsive-card><div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $program->code }}</p><a href="{{ route('admin.programs.show', $program) }}" class="mt-1 block font-semibold text-brand-900">{{ $program->name }}</a></div><span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800">{{ $program->levels_count }} tingkat</span></div><div class="mt-4 flex flex-wrap gap-2"><x-button href="{{ route('admin.programs.show', $program) }}" variant="secondary" size="sm" icon="eye">Kelola tingkat</x-button>@can('update', $program)<x-button href="{{ route('admin.programs.edit', $program) }}" variant="edit" size="sm" icon="edit">Edit</x-button>@endcan</div></article>@empty<div class="px-2 py-10 text-center"><p class="font-semibold text-slate-900">Belum ada program</p><p class="mt-2 text-sm text-slate-500">Tambahkan program untuk mulai mengatur tingkat.</p></div>@endforelse</div>
            <div class="border-t border-slate-100 px-5 sm:px-6"><x-pagination :paginator="$programs" /></div>
        </section>
    </div>
@endsection
