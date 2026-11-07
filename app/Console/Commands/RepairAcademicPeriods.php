<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\Semester;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('academic-periods:repair {--institution= : Batasi ke ID PKBM} {--apply : Terapkan koreksi status yang tidak ambigu}')]
#[Description('Inspect inconsistent active semesters and repair a sole Draft parent without changing assessments')]
class RepairAcademicPeriods extends Command
{
    public function handle(): int
    {
        $institutions = Institution::query()->when($this->option('institution'), fn ($q) => $q->whereKey($this->option('institution')))->get();
        $conflicts = 0;
        foreach ($institutions as $institution) {
            $ok = DB::transaction(function () use ($institution): bool {
                Institution::query()->whereKey($institution->id)->lockForUpdate()->firstOrFail();
                $years = AcademicYear::query()->where('institution_id', $institution->id)->lockForUpdate()->get();
                $activeSemesters = Semester::query()->whereIn('academic_year_id', $years->pluck('id'))->where('status', 'ACTIVE')->get();
                $orphans = $activeSemesters->filter(fn ($s) => $years->firstWhere('id', $s->academic_year_id)?->status?->value !== 'ACTIVE');
                if ($orphans->isEmpty()) {
                    $this->info('PKBM '.$institution->id.': status konsisten.');

                    return true;
                }
                $year = $years->firstWhere('id', $orphans->first()->academic_year_id);
                if ($orphans->count() !== 1 || $activeSemesters->count() !== 1 || $years->contains(fn ($y) => $y->status->value === 'ACTIVE') || $year?->status?->value !== 'DRAFT') {
                    $this->error('PKBM '.$institution->id.': konflik status; tidak ada perubahan otomatis.');

                    return false;
                }
                $this->info('PKBM '.$institution->id.': tahun '.$year->name.' DRAFT → ACTIVE mengikuti satu semester aktif yang sudah ada.');
                if ($this->option('apply')) {
                    $year->update(['status' => 'ACTIVE']);
                    AuditLog::query()->create(['institution_id' => $institution->id, 'action' => 'academic_period.consistency_repaired',
                        'entity_type' => AcademicYear::class, 'entity_id' => $year->id,
                        'old_value_json' => ['status' => 'DRAFT'], 'new_value_json' => ['status' => 'ACTIVE'],
                        'reason' => 'Memulihkan induk Draf yang memiliki satu semester Aktif; nilai dan arsip tidak diubah.']);
                }

                return true;
            });
            if (! $ok) {
                $conflicts++;
            }
        }
        if (! $this->option('apply')) {
            $this->comment('Pemeriksaan saja. Gunakan --apply untuk menerapkan koreksi.');
        }

        return $conflicts ? self::FAILURE : self::SUCCESS;
    }
}
