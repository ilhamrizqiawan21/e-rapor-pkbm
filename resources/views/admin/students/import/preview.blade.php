@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <div>
            <p class="text-sm font-medium text-indigo-600">Peserta Didik</p>
            <h1 class="text-2xl font-semibold text-slate-900">Preview Import</h1>
        </div>
    </div>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Total baris</p><p class="mt-1 text-2xl font-semibold">{{ $preview['total_count'] }}</p></div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4"><p class="text-xs text-emerald-700">Valid</p><p class="mt-1 text-2xl font-semibold text-emerald-800">{{ $preview['valid_count'] }}</p></div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-4"><p class="text-xs text-red-700">Gagal</p><p class="mt-1 text-2xl font-semibold text-red-800">{{ $preview['invalid_count'] }}</p></div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="text-xs text-amber-700">Baris kosong</p><p class="mt-1 text-2xl font-semibold text-amber-800">{{ $preview['blank_count'] }}</p></div>
        </div>

        <div class="flex flex-wrap gap-3">
            @if($preview['errors'] !== [])
                <a href="{{ action([\App\Http\Controllers\Admin\StudentImportController::class, 'errorReport']) }}" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700">Download error report</a>
            @endif
            <form method="POST" action="{{ action([\App\Http\Controllers\Admin\StudentImportController::class, 'store']) }}">
                @csrf
                <input type="hidden" name="preview_token" value="{{ $token }}">
                <button type="submit" @disabled($preview['valid_count'] === 0) class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Import {{ $preview['valid_count'] }} baris valid</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Baris</th>@foreach($preview['headers'] as $header)<th class="px-4 py-3 text-left">{{ $header }}</th>@endforeach<th class="px-4 py-3 text-left">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($preview['rows'] as $row)
                            <tr class="{{ $row['valid'] ? '' : 'bg-red-50' }}"><td class="whitespace-nowrap px-4 py-3">{{ $row['row'] }}</td>@foreach($row['values'] as $value)<td class="whitespace-nowrap px-4 py-3">{{ $value }}</td>@endforeach<td class="px-4 py-3">@if($row['valid'])<span class="font-semibold text-emerald-700">Valid</span>@else<span class="font-semibold text-red-700">{{ implode(' ', $row['errors']) }}</span>@endif</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
