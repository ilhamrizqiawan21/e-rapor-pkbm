@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-indigo-600">Peserta Didik</p>
                <h1 class="text-2xl font-semibold text-slate-900">Import Peserta Didik</h1>
            </div>
            <a href="{{ action([\App\Http\Controllers\Admin\StudentImportController::class, 'template']) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Download template XLSX</a>
        </div>
    </div>

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Gunakan template resmi. Import hanya menambahkan peserta didik yang valid dan tidak mengubah data yang sudah ada.
        </div>
        <form method="POST" action="{{ action([\App\Http\Controllers\Admin\StudentImportController::class, 'preview']) }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <label for="file" class="block text-sm font-semibold text-slate-800">File XLSX atau CSV</label>
            <input id="file" name="file" type="file" accept=".xlsx,.csv,.txt" required class="mt-2 block w-full rounded-lg border-slate-300 text-sm">
            <p class="mt-2 text-xs text-slate-500">Maksimal 10 MB dan 50.000 baris. CSV tersedia sebagai fallback.</p>
            @error('file') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <button type="submit" class="mt-6 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Validasi dan preview</button>
        </form>
    </div>
@endsection
