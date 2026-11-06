@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Master data</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Program pendidikan</h2>
                <p class="mt-2 text-sm text-slate-600">Kelola Paket A, Paket B, Paket C, dan program custom PKBM.</p>
            </div>
            @can('create', App\Models\Program::class)
                <x-button href="{{ route('admin.programs.create') }}">Tambah program</x-button>
            @endcan
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr><th class="px-6 py-4">Kode</th><th class="px-6 py-4">Nama program</th><th class="px-6 py-4">Tingkat</th><th class="px-6 py-4 text-right">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($programs as $program)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-semibold text-slate-900">{{ $program->code }}</td>
                                <td class="px-6 py-4 text-slate-700">{{ $program->name }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $program->levels_count }} tingkat</td>
                                <td class="px-6 py-4"><div class="flex justify-end gap-2">
                                    <x-button href="{{ route('admin.programs.show', $program) }}" variant="secondary" size="sm">Kelola tingkat</x-button>
                                    @can('update', $program)<x-button href="{{ route('admin.programs.edit', $program) }}" variant="ghost" size="sm">Edit</x-button>@endcan
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500">Belum ada program. Tambahkan program atau jalankan seed Paket A–C.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6"><x-pagination :paginator="$programs" /></div>
        </section>
    </div>
@endsection
