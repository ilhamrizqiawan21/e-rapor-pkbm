@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header eyebrow="Konfigurasi rapor" title="Pratinjau konversi nilai" description="Uji nilai terhadap rentang predikat aktif institusi." back-href="{{ route('admin.predicate-ranges.index') }}" back-text="Kembali ke rentang predikat" />
        <form method="GET" action="{{ route('admin.predicate-ranges.preview') }}" class="mb-5 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-end">
            <x-input name="score" type="number" min="0" max="100" label="Nilai" :value="old('score', $score ?? '')" required />
            <x-button type="submit">Konversi</x-button>
        </form>
        @if (isset($score))
            <div class="mb-5 rounded-2xl border {{ $result ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} p-6">
                <p class="text-sm text-slate-600">Hasil untuk nilai {{ $score }}</p>
                @if ($result)<p class="mt-2 text-2xl font-semibold text-slate-950">{{ $result->code }} · {{ $result->label }}</p><p class="mt-1 text-sm text-slate-600">Rentang {{ $result->minimum_score }}–{{ $result->maximum_score }}</p>@else<p class="mt-2 font-semibold text-amber-900">Belum ada predikat aktif yang mencakup nilai ini.</p>@endif
            </div>
        @endif
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr><x-table.th>Predikat</x-table.th><x-table.th>Rentang</x-table.th><x-table.th>Status</x-table.th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($ranges as $range)<tr><x-table.td><span class="font-semibold">{{ $range->code }}</span> · {{ $range->label }}</x-table.td><x-table.td>{{ $range->minimum_score }}–{{ $range->maximum_score }}</x-table.td><x-table.td>Aktif</x-table.td></tr>@endforeach</tbody></table></div>
    </div>
@endsection
