@extends('layouts.app', ['title' => 'Komponen UI - E-Rapor PKBM'])

@section('content')
<div class="space-y-10">
    <!-- Header -->
    <div class="border-b border-slate-200 pb-5">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Showcase Komponen UI</h1>
        <p class="mt-1 text-sm text-slate-500">
            Daftar komponen dasar sistem desain E-Rapor PKBM sesuai item TODO 01.04.
        </p>
    </div>

    <!-- 1. Token Warna & Tipografi -->
    <section class="space-y-4">
        <h2 class="text-lg font-semibold text-slate-900">1. Token Warna & Tipografi</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-11 text-xs">
            @foreach ([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950] as $shade)
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
                    <div class="h-12 bg-brand-{{ $shade }}"></div>
                    <div class="p-2 text-center font-mono">brand-{{ $shade }}</div>
                </div>
            @endforeach
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-11 text-xs">
            @foreach ([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950] as $shade)
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
                    <div class="h-12 bg-accent-{{ $shade }}"></div>
                    <div class="p-2 text-center font-mono">accent-{{ $shade }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- 2. Tombol (Button) -->
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
        <h2 class="text-lg font-semibold text-slate-900">2. Komponen Tombol (&lt;x-button&gt;)</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-button variant="primary">Primary (Brand Navy)</x-button>
            <x-button variant="secondary">Secondary (Outline)</x-button>
            <x-button variant="accent">Accent (Teal)</x-button>
            <x-button variant="success">Success</x-button>
            <x-button variant="warning">Warning</x-button>
            <x-button variant="danger">Danger</x-button>
            <x-button variant="ghost">Ghost</x-button>
            <x-button variant="primary" disabled>Disabled</x-button>
        </div>

        <div class="pt-2">
            <p class="text-xs font-medium text-slate-500 mb-2 uppercase tracking-wider">Variasi Ukuran</p>
            <div class="flex flex-wrap items-center gap-3">
                <x-button size="xs">Ukuran XS</x-button>
                <x-button size="sm">Ukuran SM</x-button>
                <x-button size="md">Ukuran MD</x-button>
                <x-button size="lg">Ukuran LG</x-button>
            </div>
        </div>
    </section>

    <!-- 3. Form Input, Select, Textarea -->
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-6">
        <h2 class="text-lg font-semibold text-slate-900">3. Form Input, Select, & Textarea</h2>
        <div class="grid gap-6 md:grid-cols-3">
            <x-input
                label="Nama Lengkap"
                name="full_name"
                placeholder="Contoh: Budi Santoso"
                hint="Sesuai akta kelahiran atau ijazah"
                required
            />
            <x-input
                label="Nomor Induk Siswa Nasional (NISN)"
                name="nisn"
                placeholder="10 digit NISN"
                value="0012345678"
            />
            <x-input
                label="Email Pengguna"
                name="email"
                type="email"
                value="invalid-email"
                error="Format alamat email tidak valid"
            />
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <x-select
                label="Tingkat / Paket Belajar"
                name="package_level"
                placeholder="-- Pilih Paket Belajar --"
                :options="[
                    'paket_a' => 'Paket A (Setara SD)',
                    'paket_b' => 'Paket B (Setara SMP)',
                    'paket_c' => 'Paket C (Setara SMA)',
                ]"
                required
            />

            <x-select
                label="Status Kelulusan"
                name="status"
                error="Status kelulusan belum ditentukan"
            >
                <option value="">-- Pilih Status --</option>
                <option value="lulus">Lulus</option>
                <option value="belum">Belum Lulus</option>
            </x-select>
        </div>

        <div>
            <x-textarea
                label="Catatan Perkembangan Akademik"
                name="academic_notes"
                placeholder="Tuliskan catatan evaluasi atau capaian kompetensi peserta didik..."
                hint="Maksimal 500 karakter untuk narasi rapor."
                rows="3"
            />
        </div>
    </section>

    <!-- 4. Badge Status -->
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
        <h2 class="text-lg font-semibold text-slate-900">4. Badge Status Alur Rapor (&lt;x-badge&gt;)</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-badge variant="DRAFT" dot />
            <x-badge variant="SUBMITTED" dot />
            <x-badge variant="APPROVED" dot />
            <x-badge variant="REVISED" dot />
            <x-badge variant="LOCKED" dot />
            <x-badge variant="ACTIVE" dot />
            <x-badge variant="INACTIVE" dot />
        </div>
        <div class="flex flex-wrap items-center gap-3 pt-2">
            <x-badge variant="brand">Brand Badge</x-badge>
            <x-badge variant="accent">Accent Badge</x-badge>
            <x-badge variant="info">Info Badge</x-badge>
            <x-badge variant="success">Success Badge</x-badge>
            <x-badge variant="warning">Warning Badge</x-badge>
            <x-badge variant="danger">Danger Badge</x-badge>
        </div>
    </section>

    <!-- 5. Tabel Data -->
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">5. Komponen Tabel (&lt;x-table&gt;)</h2>
            <x-button size="sm" variant="secondary">Ekspor Data</x-button>
        </div>

        <x-table>
            <x-slot:head>
                <tr>
                    <x-table.th>NISN</x-table.th>
                    <x-table.th>Nama Peserta Didik</x-table.th>
                    <x-table.th>Kelas / Rombel</x-table.th>
                    <x-table.th align="center">Status Rapor</x-table.th>
                    <x-table.th align="right">Aksi</x-table.th>
                </tr>
            </x-slot:head>

            <x-table.tr>
                <x-table.td class="font-mono text-xs">0019283741</x-table.td>
                <x-table.td class="font-medium text-slate-900">Ahmad Fauzi</x-table.td>
                <x-table.td>Paket C - Kelas 10A</x-table.td>
                <x-table.td align="center"><x-badge variant="APPROVED" dot /></x-table.td>
                <x-table.td align="right">
                    <x-button size="xs" variant="ghost">Lihat</x-button>
                </x-table.td>
            </x-table.tr>

            <x-table.tr>
                <x-table.td class="font-mono text-xs">0019283742</x-table.td>
                <x-table.td class="font-medium text-slate-900">Siti Nurhaliza</x-table.td>
                <x-table.td>Paket C - Kelas 10A</x-table.td>
                <x-table.td align="center"><x-badge variant="SUBMITTED" dot /></x-table.td>
                <x-table.td align="right">
                    <x-button size="xs" variant="ghost">Lihat</x-button>
                </x-table.td>
            </x-table.tr>

            <x-table.tr>
                <x-table.td class="font-mono text-xs">0019283743</x-table.td>
                <x-table.td class="font-medium text-slate-900">Rian Pratama</x-table.td>
                <x-table.td>Paket C - Kelas 10A</x-table.td>
                <x-table.td align="center"><x-badge variant="LOCKED" dot /></x-table.td>
                <x-table.td align="right">
                    <x-button size="xs" variant="ghost">Lihat</x-button>
                </x-table.td>
            </x-table.tr>
        </x-table>

        <x-pagination />
    </section>

    <!-- 6. Modal & Toast Interaktif -->
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
        <h2 class="text-lg font-semibold text-slate-900">6. Modal Dialog & Toast Interaktif</h2>
        <p class="text-sm text-slate-600">Klik tombol di bawah ini untuk memicu modal dialog atau toast notification.</p>

        <div class="flex flex-wrap items-center gap-3">
            <x-button
                variant="primary"
                @click="$dispatch('open-modal', 'demo-modal')"
            >
                Buka Modal Dialog
            </x-button>

            <x-button
                variant="success"
                @click="$dispatch('toast', { type: 'success', title: 'Berhasil', message: 'Data nilai semester berhasil disimpan!' })"
            >
                Toast Berhasil
            </x-button>

            <x-button
                variant="danger"
                @click="$dispatch('toast', { type: 'danger', title: 'Gagal', message: 'Terjadi kesalahan saat mengunci rapor.' })"
            >
                Toast Error
            </x-button>

            <x-button
                variant="warning"
                @click="$dispatch('toast', { type: 'warning', title: 'Peringatan', message: 'Terdapat 3 peserta didik yang belum memiliki nilai KKM.' })"
            >
                Toast Warning
            </x-button>
        </div>

        <x-modal name="demo-modal" title="Konfirmasi Pengesahan Rapor">
            <p>
                Apakah Anda yakin ingin menyetujui dan menandatangani rapor kelas ini? Setelah disetujui, perubahan nilai memerlukan izin administratif.
            </p>
            <x-slot:footer>
                <x-button variant="secondary" size="sm" @click="$dispatch('close-modal', 'demo-modal')">
                    Batal
                </x-button>
                <x-button variant="primary" size="sm" @click="$dispatch('close-modal', 'demo-modal'); $dispatch('toast', { type: 'success', message: 'Rapor berhasil disetujui.' })">
                    Ya, Setujui Rapor
                </x-button>
            </x-slot:footer>
        </x-modal>
    </section>

    <!-- 7. State UI: Empty, Loading, & Error -->
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-6">
        <h2 class="text-lg font-semibold text-slate-900">7. State UI (Empty, Loading, Error)</h2>
        
        <div class="grid gap-6 md:grid-cols-3">
            <!-- Empty State -->
            <x-empty-state
                title="Belum Ada Rapor"
                description="Peserta didik ini belum memiliki rekap nilai semester berjalan."
                actionText="Buat Nilai Baru"
            />

            <!-- Loading State -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <x-loading text="Memuat leger nilai kelas..." />
            </div>

            <!-- Error State -->
            <x-error-state
                title="Gagal Menghubungkan Server"
                message="Koneksi database lokal terputus saat mengambil data siswa."
                retryText="Muat Ulang Halaman"
            />
        </div>
    </section>
</div>
@endsection

