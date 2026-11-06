@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Pengaturan PKBM / operasional" title="Backup dan restore" description="Kelola salinan database secara aman. Restore mengganti database aktif dan selalu membuat safety backup terlebih dahulu." />

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->has('backup'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first('backup') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-700">Backup manual</p>
                <h3 class="mt-2 text-lg font-semibold text-slate-950">Buat salinan sekarang</h3>
                <p class="mt-2 text-sm text-slate-600">Driver aktif: <strong>{{ $driver }}</strong>. Backup disimpan pada disk private dengan retensi {{ $retentionDays }} hari.</p>
                <form method="POST" action="{{ route('admin.backups.store') }}" class="mt-5">
                    @csrf
                    <x-button type="submit">Buat backup database</x-button>
                </form>
            </section>

            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">Restore destruktif</p>
                <h3 class="mt-2 text-lg font-semibold text-amber-950">Pulihkan dari file backup</h3>
                <p class="mt-2 text-sm text-amber-900">Jadwal backup online: {{ $schedule }}. Ketik <strong>RESTORE</strong> untuk mengonfirmasi penggantian database aktif.</p>
                <form method="POST" action="{{ route('admin.backups.restore') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <div><label for="backup" class="block text-sm font-semibold text-amber-950">File backup</label><input id="backup" name="backup" type="file" required accept=".sqlite,.db,.sql,.gz" class="mt-2 block w-full rounded-lg border-amber-300 bg-white text-sm"></div>
                    <div><label for="confirmation" class="block text-sm font-semibold text-amber-950">Konfirmasi</label><input id="confirmation" name="confirmation" type="text" required autocomplete="off" class="mt-2 block w-full rounded-lg border-amber-300 bg-white text-sm" placeholder="RESTORE"></div>
                    <x-button type="submit" variant="danger">Pulihkan database</x-button>
                </form>
            </section>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            <div class="border-b border-slate-100 px-6 py-5"><h3 class="font-semibold text-slate-950">Arsip backup</h3><p class="mt-1 text-sm text-slate-500">File berada pada penyimpanan private dan tidak dapat diakses dari URL publik.</p></div>
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-[760px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50"><tr><x-table.th>Nama file</x-table.th><x-table.th>Ukuran</x-table.th><x-table.th>Diubah</x-table.th><x-table.th>SHA-256</x-table.th><x-table.th align="right">Aksi</x-table.th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($backups as $backup)
                            <tr><x-table.td><span class="font-medium text-slate-900">{{ $backup['name'] }}</span></x-table.td><x-table.td>{{ number_format($backup['size'] / 1024, 1, ',', '.') }} KB</x-table.td><x-table.td>{{ $backup['modified_at']->format('d/m/Y H:i') }}</x-table.td><x-table.td><code class="text-xs text-slate-500">{{ $backup['checksum'] }}</code></x-table.td><x-table.td align="right"><x-button href="{{ route('admin.backups.download', ['backup' => $backup['name']]) }}" size="sm" variant="secondary">Unduh</x-button></x-table.td></tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada backup database.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="space-y-3 p-4 md:hidden">
                @forelse ($backups as $backup)
                    <article class="rounded-xl border border-slate-200 p-4" data-responsive-card>
                        <div class="flex items-start justify-between gap-3"><p class="break-all text-sm font-semibold text-slate-900">{{ $backup['name'] }}</p><span class="shrink-0 text-xs text-slate-500">{{ number_format($backup['size'] / 1024, 1, ',', '.') }} KB</span></div>
                        <p class="mt-2 text-xs text-slate-500">Diubah {{ $backup['modified_at']->format('d/m/Y H:i') }}</p>
                        <div class="mt-3 rounded-lg bg-slate-50 p-3"><p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">SHA-256</p><code class="mt-1 block break-all text-[11px] text-slate-600">{{ $backup['checksum'] }}</code></div>
                        <div class="mt-3"><x-button href="{{ route('admin.backups.download', ['backup' => $backup['name']]) }}" size="sm" variant="secondary">Unduh backup</x-button></div>
                    </article>
                @empty
                    <p class="px-2 py-8 text-center text-sm text-slate-500">Belum ada backup database.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
