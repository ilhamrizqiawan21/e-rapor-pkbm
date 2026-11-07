<x-modal name="semester-start-{{ $academicYear->id }}" title="Mulai semester genap" max-width="4xl">
    <div class="space-y-5">
        <p class="text-sm text-slate-600">{{ $academicYear->name }} · Semester Ganjil → Semester Genap. Kelas dan anggota tetap; penilaian semester baru dimulai kosong.</p>
        <div class="overflow-x-auto rounded-xl border border-slate-200"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50"><tr><th class="p-3">Kelas</th><th class="p-3">Siswa aktif</th><th class="p-3">Rapor belum lengkap</th><th class="p-3">Tindakan</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse($state['groups'] as $group)<tr><td class="p-3 font-medium">{{ $group['name'] }}</td><td class="p-3">{{ $group['students'] }}</td><td class="p-3">{{ $group['missing_reports'] }}</td><td class="p-3"><a class="font-semibold text-blue-700 underline" href="{{ route('admin.report-cards.index', ['academic_year_id' => $academicYear->id, 'semester_id' => $state['semester']->id, 'class_id' => $group['class_id']]) }}">Periksa rapor</a></td></tr>@empty<tr><td colspan="4" class="p-3 text-slate-500">Belum ada siswa aktif.</td></tr>@endforelse
        </tbody></table></div>
        @if(! $state['ready'])<div role="status" class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900"><p class="font-semibold">Lengkapi sebelum melanjutkan</p>@foreach($state['blockers'] as $message)<p class="mt-1">{{ $message }}</p>@endforeach</div>@else<p class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">Seluruh rapor siswa aktif lengkap. Semester Genap siap dimulai.</p>@endif
        @if(auth()->user()->hasRole('ADMIN'))
        <form method="POST" action="{{ route('admin.academic-years.transition.store', $academicYear) }}" class="space-y-4">@csrf<input type="hidden" name="fingerprint" value="{{ $state['fingerprint'] }}">
            <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="confirm" value="1" required @disabled(! $state['ready']) class="mt-1 rounded border-slate-300 text-brand-700">Saya sudah memeriksa rapor dan akan menutup semester Ganjil.</label>
            <div class="flex flex-wrap justify-end gap-2"><x-button type="button" variant="cancel" @click="$dispatch('close-modal', 'semester-start-{{ $academicYear->id }}')">Batal</x-button><x-button type="submit" :disabled="! $state['ready']">Mulai semester genap</x-button></div>
        </form>
        @endif
    </div>
</x-modal>
