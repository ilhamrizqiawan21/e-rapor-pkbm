@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-header eyebrow="Program {{ $program->code }}" title="{{ $program->name }}" description="Kelola nama tingkat yang akan dipakai pada kelas dan rapor." back-href="{{ route('admin.programs.index') }}" back-text="Kembali ke program pendidikan">
            @can('update', $program)<x-button href="#" @click.prevent="$dispatch('open-modal', 'program-detail-edit')" variant="edit" icon="edit">Edit program</x-button>@endcan
        </x-page-header>
        @if (session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>@endif
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><div><h3 class="font-semibold text-slate-900">Daftar tingkat</h3><p class="mt-1 text-xs text-slate-500">Nomor tingkat 1–12; nama dapat disesuaikan.</p></div>@can('update', $program)<x-button href="#" @click.prevent="$dispatch('open-modal', 'level-create')" size="sm" icon="add">Tambah tingkat</x-button>@endcan</div>
            <div class="divide-y divide-slate-100">
                @forelse ($program->levels as $level)
                    <div class="flex items-center justify-between px-6 py-4"><div><span class="font-semibold text-slate-900">Tingkat {{ $level->number }}</span><span class="ml-3 text-slate-600">{{ $level->name }}</span></div><div class="flex gap-2"><x-button href="{{ route('admin.programs.levels.show', [$program, $level]) }}" variant="detail" size="sm" icon="eye">Detail</x-button>@can('update', $level)<x-button href="#" @click.prevent="$dispatch('open-modal', 'level-edit-{{ $level->id }}')" variant="edit" size="sm" icon="edit">Edit</x-button>@endcan</div></div>
                @empty
                    <p class="px-6 py-12 text-center text-slate-500">Belum ada tingkat pada program ini.</p>
                @endforelse
            </div>
        </section>

        @can('update', $program)
            <x-modal name="program-detail-edit" title="Edit program" max-width="2xl">
                <form method="POST" action="{{ route('admin.programs.update', $program) }}" class="space-y-5">
                    @csrf @method('PUT')
                    @include('admin.programs._form', ['program' => $program])
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <x-button type="button" variant="cancel" icon="x" @click="$dispatch('close-modal', 'program-detail-edit')">Batal</x-button>
                        <x-button type="submit" icon="save">Simpan perubahan</x-button>
                    </div>
                </form>
            </x-modal>

            <x-modal name="level-create" title="Tambah tingkat" max-width="2xl">
                <form method="POST" action="{{ route('admin.programs.levels.store', $program) }}" class="space-y-5">
                    @csrf
                    @include('admin.programs.levels._form', ['level' => $modalLevel])
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <x-button type="button" variant="cancel" icon="x" @click="$dispatch('close-modal', 'level-create')">Batal</x-button>
                        <x-button type="submit" icon="save">Simpan tingkat</x-button>
                    </div>
                </form>
            </x-modal>
        @endcan

        @foreach ($program->levels as $level)
            @can('update', $level)
                <x-modal name="level-edit-{{ $level->id }}" title="Edit tingkat" max-width="2xl">
                    <form method="POST" action="{{ route('admin.programs.levels.update', [$program, $level]) }}" class="space-y-5">
                        @csrf @method('PUT')
                        @include('admin.programs.levels._form', ['level' => $level])
                        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                            <x-button type="button" variant="cancel" icon="x" @click="$dispatch('close-modal', 'level-edit-{{ $level->id }}')">Batal</x-button>
                            <x-button type="submit" icon="save">Simpan perubahan</x-button>
                        </div>
                    </form>
                </x-modal>
            @endcan
        @endforeach
    </div>
@endsection
