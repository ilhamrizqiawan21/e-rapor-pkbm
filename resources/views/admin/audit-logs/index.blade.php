@extends('layouts.app')

@php
    $actionLabels = [
        'report_card.submitted' => 'Ajukan rapor',
        'report_card.reviewed' => 'Tinjau rapor',
        'report_card.rejected' => 'Kembalikan rapor',
        'report_card.approved' => 'Setujui rapor',
        'report_card.locked' => 'Kunci rapor',
        'report_card.unlocked' => 'Buka kunci rapor',
        'auth.login' => 'Masuk',
        'auth.logout' => 'Keluar',
        'user.role_changed' => 'Perubahan peran',
        'student.created' => 'Tambah peserta didik',
        'student.viewed' => 'Akses peserta didik',
        'student.updated' => 'Ubah peserta didik',
        'student.deleted' => 'Hapus peserta didik',
        'report_grade.updated' => 'Ubah nilai',
        'ledger.exported' => 'Ekspor leger',
        'report_card.exported' => 'Ekspor rapor',
        'report_card.viewed' => 'Akses rapor',
        'backup.created' => 'Buat cadangan',
        'backup.restored' => 'Pulihkan cadangan',
        'backup.downloaded' => 'Unduh cadangan',
    ];
    $entityLabels = [
        'report_card' => 'Rapor',
        'report_grade' => 'Nilai',
        'student' => 'Peserta didik',
        'enrollment' => 'Penempatan',
        'institution' => 'Institusi',
        'user' => 'Akun pengguna',
        'ledger' => 'Leger',
        'backup' => 'Cadangan',
    ];
@endphp

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-header eyebrow="Tata kelola / audit" title="Histori aktivitas" description="Riwayat aktivitas penting pada institusi ini. Catatan tidak dapat diubah atau dihapus." />

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <x-select name="actor_id" id="actor_id" label="Pengguna" :selected="$filters['actor_id'] ?? null" placeholder="Semua pengguna">
                    @foreach ($actors as $actor)
                        <option value="{{ $actor->id }}" @selected((string) ($filters['actor_id'] ?? '') === (string) $actor->id)>{{ $actor->name }}{{ $actor->username ? ' · '.$actor->username : '' }}</option>
                    @endforeach
                </x-select>
                <x-select name="action" id="action" label="Aksi" :selected="$filters['action'] ?? null" placeholder="Semua aksi">
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $actionLabels[$action] ?? $action }}</option>
                    @endforeach
                </x-select>
                <x-select name="entity_type" id="entity_type" label="Objek" :selected="$filters['entity_type'] ?? null" placeholder="Semua objek">
                    @foreach ($entityTypes as $key => $type)
                        <option value="{{ $key }}" @selected(($filters['entity_type'] ?? '') === $key)>{{ $entityLabels[$key] ?? class_basename($type) }}</option>
                    @endforeach
                </x-select>
                <div>
                    <label for="entity_id" class="block text-sm font-semibold text-slate-800">ID objek</label>
                    <input id="entity_id" name="entity_id" type="number" min="1" value="{{ $filters['entity_id'] ?? '' }}" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Semua ID">
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-semibold text-slate-800">Dari tanggal</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-semibold text-slate-800">Sampai tanggal</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div class="flex items-end gap-2 md:col-span-2 xl:col-span-6">
                    <x-button type="submit" icon="filter">Terapkan filter</x-button>
                    <x-button href="{{ route('admin.audit-logs.index') }}" variant="secondary" icon="x">Reset</x-button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-[1100px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <x-table.th>Waktu</x-table.th>
                            <x-table.th>Pengguna</x-table.th>
                            <x-table.th>Aksi</x-table.th>
                            <x-table.th>Objek</x-table.th>
                            <x-table.th>Perubahan</x-table.th>
                            <x-table.th>Alasan</x-table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($auditLogs as $auditLog)
                            <tr class="align-top hover:bg-slate-50">
                                <x-table.td>
                                    <time datetime="{{ $auditLog->created_at?->toIso8601String() }}" class="font-medium text-slate-900">{{ $auditLog->created_at?->format('d/m/Y H:i') }}</time>
                                </x-table.td>
                                <x-table.td>
                                    <p class="font-medium text-slate-900">{{ $auditLog->user?->name ?? 'Sistem / akun dihapus' }}</p>
                                    @if ($auditLog->user?->username)<p class="mt-1 text-xs text-slate-500">{{ $auditLog->user->username }}</p>@endif
                                </x-table.td>
                                <x-table.td><x-badge variant="info" dot>{{ $actionLabels[$auditLog->action] ?? $auditLog->action }}</x-badge></x-table.td>
                                <x-table.td>
                                    <p class="font-medium text-slate-900">{{ class_basename($auditLog->entity_type) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">ID objek {{ $auditLog->entity_id }}</p>
                                    @if ($auditLog->reportCard?->enrollment?->student)<p class="mt-1 text-xs text-slate-500">{{ $auditLog->reportCard->enrollment->student->full_name }}</p>@endif
                                </x-table.td>
                                <x-table.td>
                                    @if ($auditLog->old_value_json || $auditLog->new_value_json)
                                        <details>
                                            <summary class="cursor-pointer text-xs font-semibold text-brand-800">Lihat detail</summary>
                                            <dl class="mt-2 space-y-1 text-xs text-slate-600">
                                                @if ($auditLog->old_value_json)<div><dt class="font-semibold">Nilai sebelum</dt><dd class="max-w-64 break-words">{{ json_encode($auditLog->old_value_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</dd></div>@endif
                                                @if ($auditLog->new_value_json)<div><dt class="font-semibold">Nilai sesudah</dt><dd class="max-w-64 break-words">{{ json_encode($auditLog->new_value_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</dd></div>@endif
                                            </dl>
                                        </details>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </x-table.td>
                                <x-table.td><span class="max-w-56 whitespace-normal text-slate-600">{{ $auditLog->reason ?: '—' }}</span></x-table.td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada histori yang sesuai filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="space-y-3 p-4 md:hidden">
                @forelse ($auditLogs as $auditLog)
                    <article class="rounded-xl border border-slate-200 p-4" data-responsive-card>
                        <div class="flex items-start justify-between gap-3">
                            <div><time datetime="{{ $auditLog->created_at?->toIso8601String() }}" class="text-xs font-medium text-slate-500">{{ $auditLog->created_at?->format('d/m/Y H:i') }}</time><p class="mt-1 font-semibold text-slate-900">{{ $auditLog->user?->name ?? 'Sistem / akun dihapus' }}</p></div>
                            <x-badge variant="info" dot>{{ $actionLabels[$auditLog->action] ?? $auditLog->action }}</x-badge>
                        </div>
                        <p class="mt-3 text-sm font-medium text-slate-900">{{ $entityLabels[$auditLog->entity_type] ?? class_basename($auditLog->entity_type) }} <span class="font-normal text-slate-500">· ID objek {{ $auditLog->entity_id }}</span></p>
                        @if ($auditLog->reportCard?->enrollment?->student)<p class="mt-1 text-xs text-slate-500">{{ $auditLog->reportCard->enrollment->student->full_name }}</p>@endif
                        @if ($auditLog->old_value_json || $auditLog->new_value_json)<details class="mt-3"><summary class="cursor-pointer text-xs font-semibold text-brand-800">Lihat detail perubahan</summary><dl class="mt-2 space-y-1 text-xs text-slate-600">@if ($auditLog->old_value_json)<div><dt class="font-semibold">Nilai sebelum</dt><dd class="break-words">{{ json_encode($auditLog->old_value_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</dd></div>@endif @if ($auditLog->new_value_json)<div><dt class="font-semibold">Nilai sesudah</dt><dd class="break-words">{{ json_encode($auditLog->new_value_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</dd></div>@endif</dl></details>@endif
                        <p class="mt-3 text-xs text-slate-600">{{ $auditLog->reason ?: 'Tidak ada alasan tambahan.' }}</p>
                    </article>
                @empty
                    <p class="px-2 py-8 text-center text-sm text-slate-500">Belum ada histori yang sesuai filter.</p>
                @endforelse
            </div>
            @if ($auditLogs->hasPages())
                <div class="border-t border-slate-100 px-5 py-3"><x-pagination :paginator="$auditLogs" /></div>
            @endif
        </section>
    </div>
@endsection
