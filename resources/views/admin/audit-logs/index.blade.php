@extends('layouts.app')

@php
    $actionLabels = [
        'report_card.submitted' => 'Submit rapor',
        'report_card.reviewed' => 'Review rapor',
        'report_card.rejected' => 'Reject rapor',
        'report_card.approved' => 'Approve rapor',
        'report_card.locked' => 'Lock rapor',
        'report_card.unlocked' => 'Unlock rapor',
        'auth.login' => 'Login',
        'auth.logout' => 'Logout',
        'user.role_changed' => 'Perubahan role',
        'student.created' => 'Tambah peserta didik',
        'student.viewed' => 'Akses peserta didik',
        'student.updated' => 'Ubah peserta didik',
        'student.deleted' => 'Hapus peserta didik',
        'report_grade.updated' => 'Ubah nilai',
        'ledger.exported' => 'Export leger',
        'report_card.exported' => 'Export rapor',
        'report_card.viewed' => 'Akses rapor',
        'backup.created' => 'Buat backup',
        'backup.restored' => 'Restore backup',
        'backup.downloaded' => 'Unduh backup',
    ];
    $entityLabels = [
        'report_card' => 'Rapor',
        'report_grade' => 'Nilai',
        'student' => 'Peserta didik',
        'enrollment' => 'Penempatan',
        'institution' => 'Institusi',
        'user' => 'Akun pengguna',
        'ledger' => 'Leger',
        'backup' => 'Backup',
    ];
@endphp

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-700">Tata kelola</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Histori audit</h2>
            <p class="mt-2 text-sm text-slate-600">Riwayat aktivitas penting pada institusi ini. Data bersifat immutable dan hanya dapat ditambahkan oleh proses domain.</p>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div>
                    <label for="actor_id" class="block text-sm font-semibold text-slate-800">Pengguna</label>
                    <select id="actor_id" name="actor_id" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Semua actor</option>
                        @foreach ($actors as $actor)
                            <option value="{{ $actor->id }}" @selected((string) ($filters['actor_id'] ?? '') === (string) $actor->id)>{{ $actor->name }}{{ $actor->username ? ' · '.$actor->username : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="action" class="block text-sm font-semibold text-slate-800">Aksi</label>
                    <select id="action" name="action" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Semua aksi</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $actionLabels[$action] ?? $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="entity_type" class="block text-sm font-semibold text-slate-800">Objek</label>
                    <select id="entity_type" name="entity_type" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Semua entity</option>
                        @foreach ($entityTypes as $key => $type)
                            <option value="{{ $key }}" @selected(($filters['entity_type'] ?? '') === $key)>{{ $entityLabels[$key] ?? class_basename($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="entity_id" class="block text-sm font-semibold text-slate-800">ID entity</label>
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
                    <x-button type="submit">Terapkan filter</x-button>
                    <x-button href="{{ route('admin.audit-logs.index') }}" variant="secondary">Reset</x-button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-[1100px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <x-table.th>Waktu</x-table.th>
                            <x-table.th>Actor</x-table.th>
                            <x-table.th>Aksi</x-table.th>
                            <x-table.th>Entity</x-table.th>
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
                                    <p class="mt-1 text-xs text-slate-500">ID {{ $auditLog->entity_id }}</p>
                                    @if ($auditLog->reportCard?->enrollment?->student)<p class="mt-1 text-xs text-slate-500">{{ $auditLog->reportCard->enrollment->student->full_name }}</p>@endif
                                </x-table.td>
                                <x-table.td>
                                    @if ($auditLog->old_value_json || $auditLog->new_value_json)
                                        <details>
                                            <summary class="cursor-pointer text-xs font-semibold text-brand-800">Lihat detail</summary>
                                            <dl class="mt-2 space-y-1 text-xs text-slate-600">
                                                @if ($auditLog->old_value_json)<div><dt class="font-semibold">Sebelum</dt><dd class="max-w-64 break-words">{{ json_encode($auditLog->old_value_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</dd></div>@endif
                                                @if ($auditLog->new_value_json)<div><dt class="font-semibold">Sesudah</dt><dd class="max-w-64 break-words">{{ json_encode($auditLog->new_value_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</dd></div>@endif
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
                        <p class="mt-3 text-sm font-medium text-slate-900">{{ $entityLabels[$auditLog->entity_type] ?? class_basename($auditLog->entity_type) }} <span class="font-normal text-slate-500">· ID {{ $auditLog->entity_id }}</span></p>
                        @if ($auditLog->reportCard?->enrollment?->student)<p class="mt-1 text-xs text-slate-500">{{ $auditLog->reportCard->enrollment->student->full_name }}</p>@endif
                        @if ($auditLog->old_value_json || $auditLog->new_value_json)<details class="mt-3"><summary class="cursor-pointer text-xs font-semibold text-brand-800">Lihat detail perubahan</summary><dl class="mt-2 space-y-1 text-xs text-slate-600">@if ($auditLog->old_value_json)<div><dt class="font-semibold">Sebelum</dt><dd class="break-words">{{ json_encode($auditLog->old_value_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</dd></div>@endif @if ($auditLog->new_value_json)<div><dt class="font-semibold">Sesudah</dt><dd class="break-words">{{ json_encode($auditLog->new_value_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</dd></div>@endif</dl></details>@endif
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
