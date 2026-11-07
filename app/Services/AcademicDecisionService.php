<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademicDecision;
use App\Models\AcademicDecisionBatch;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\ReportCard;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AcademicDecisionService
{
    public function options(int $level): array
    {
        return in_array($level, [6, 9, 12], true)
            ? ['GRADUATE' => 'Lulus paket', 'NOT_GRADUATED' => 'Belum lulus']
            : ['PROMOTE' => 'Naik kelas', 'REPEAT' => 'Tetap tingkat'];
    }

    public function current(Enrollment $enrollment, ?AcademicDecision $decision): ?AcademicDecision
    {
        return $decision && $decision->decision !== 'UNDECIDED' && $decision->enrollment_id === $enrollment->id
            && $decision->class_id === $enrollment->class_id
            && $decision->academic_year_id === $enrollment->academic_year_id ? $decision : null;
    }

    public function batch(AcademicClass $class): ?AcademicDecisionBatch
    {
        return AcademicDecisionBatch::query()
            ->where('academic_year_id', $class->academic_year_id)
            ->where('class_id', $class->getKey())
            ->first();
    }

    public function forReport(ReportCard $report): ?AcademicDecision
    {
        $report->loadMissing(['semester', 'enrollment']);
        if ($report->semester?->code?->value !== 'GENAP') {
            return null;
        }
        $enrollment = Enrollment::query()->where('student_id', $report->enrollment->student_id)
            ->where('academic_year_id', $report->semester->academic_year_id)
            ->where('enrollment_status', 'ACTIVE')->first() ?? $report->enrollment;
        $decision = AcademicDecision::query()->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $enrollment->academic_year_id)->first();

        $class = $enrollment->academicClass;
        $batch = $class ? $this->batch($class) : null;
        if (! $batch || $batch->status !== 'FINALIZED') {
            return null;
        }

        return $this->current($enrollment, $decision);
    }

    public function label(string $decision, int $level, string $program): string
    {
        return match ($decision) {
            'PROMOTE' => 'Naik ke tingkat '.($level + 1),
            'REPEAT' => 'Tetap di tingkat '.$level,
            'GRADUATE' => 'Lulus '.$program,
            'NOT_GRADUATED' => 'Belum lulus '.$program,
            'UNDECIDED' => '',
        };
    }

    /** @param array<int, array{decision: ?string, revision?: int}> $rows */
    public function save(User $actor, AcademicClass $class, array $rows): void
    {
        abort_unless($actor->isActive() && $actor->hasRole('GURU') && ! $actor->hasAnyRole(['ADMIN', 'KEPSEK']), 403);
        abort_unless($class->academicYear->institution_id === $actor->institution_id, 404);
        abort_unless($class->homeroom_teacher_id === $actor->id, 403);
        DB::transaction(function () use ($actor, $class, $rows): void {
            Institution::query()->whereKey($actor->institution_id)->lockForUpdate()->firstOrFail();
            $class = AcademicClass::query()->with(['academicYear', 'level', 'program'])->lockForUpdate()->findOrFail($class->id);
            abort_unless($class->homeroom_teacher_id === $actor->id, 403);
            $semester = Semester::query()->where('academic_year_id', $class->academic_year_id)->where('code', 'GENAP')->first();
            if ($class->academicYear->status->value !== 'ACTIVE' || $semester?->status?->value !== 'ACTIVE') {
                throw ValidationException::withMessages(['decision' => 'Keputusan hanya dapat diisi pada semester Genap aktif sebelum tahun ditutup.']);
            }
            $batch = AcademicDecisionBatch::query()->where('academic_year_id', $class->academic_year_id)
                ->where('class_id', $class->id)->lockForUpdate()->first();
            if ($batch?->status === 'FINALIZED') {
                throw ValidationException::withMessages(['decision' => 'Keputusan kelas sudah ditetapkan. Admin harus membuka koreksi terlebih dahulu.']);
            }
            $enrollments = $class->enrollments()->where('enrollment_status', 'ACTIVE')->lockForUpdate()->get()->keyBy('id');
            foreach ($rows as $id => $row) {
                $enrollment = $enrollments->get($id);
                abort_unless($enrollment, 404);
                $code = $row['decision'] ?? null;
                $existing = AcademicDecision::query()->where('student_id', $enrollment->student_id)->where('academic_year_id', $class->academic_year_id)->lockForUpdate()->first();
                if (blank($code) && ! $this->current($enrollment, $existing)) {
                    continue;
                }
                $code = blank($code) ? 'UNDECIDED' : $code;
                if ($code !== 'UNDECIDED' && ! array_key_exists($code, $this->options((int) $class->level->number))) {
                    throw ValidationException::withMessages(['decision' => 'Keputusan tidak sesuai tingkat kelas.']);
                }
                if ((int) ($row['revision'] ?? 0) !== (int) ($existing?->revision ?? 0)) {
                    throw ValidationException::withMessages(['decision' => 'Keputusan berubah sejak halaman dibuka. Muat ulang sebelum menyimpan.']);
                }
                if ($this->current($enrollment, $existing)?->decision === $code) {
                    continue;
                }
                $before = $existing?->getAttributes();
                $decision = AcademicDecision::query()->updateOrCreate(
                    ['student_id' => $enrollment->student_id, 'academic_year_id' => $class->academic_year_id],
                    ['semester_id' => $semester->id, 'enrollment_id' => $enrollment->id, 'class_id' => $class->id,
                        'decided_by' => $actor->id, 'decision' => $code,
                        'label_snapshot' => $this->label($code, (int) $class->level->number, $class->program->name),
                        'revision' => ($existing?->revision ?? 0) + 1, 'decided_at' => now()],
                );
                AuditLog::query()->create(['institution_id' => $actor->institution_id, 'user_id' => $actor->id,
                    'action' => 'academic_decision.saved', 'entity_type' => AcademicDecision::class, 'entity_id' => $decision->id,
                    'old_value_json' => $before, 'new_value_json' => $decision->getAttributes()]);
            }
            if (! $batch && AcademicDecision::query()->where('academic_year_id', $class->academic_year_id)
                ->where('class_id', $class->id)->exists()) {
                AcademicDecisionBatch::query()->create([
                    'institution_id' => $actor->institution_id,
                    'academic_year_id' => $class->academic_year_id,
                    'semester_id' => $semester->id,
                    'class_id' => $class->id,
                    'status' => 'DRAFT',
                    'revision' => 1,
                ]);
            }
        });
    }

    public function finalize(User $actor, AcademicClass $class): void
    {
        abort_unless($actor->isActive() && $actor->hasRole('GURU') && ! $actor->hasAnyRole(['ADMIN', 'KEPSEK']), 403);
        abort_unless($class->academicYear->institution_id === $actor->institution_id, 404);
        abort_unless($class->homeroom_teacher_id === $actor->id, 403);

        DB::transaction(function () use ($actor, $class): void {
            Institution::query()->whereKey($actor->institution_id)->lockForUpdate()->firstOrFail();
            $class = AcademicClass::query()->with(['academicYear', 'level', 'program'])->lockForUpdate()->findOrFail($class->id);
            abort_unless($class->homeroom_teacher_id === $actor->id, 403);
            $semester = Semester::query()->where('academic_year_id', $class->academic_year_id)->where('code', 'GENAP')->first();
            if ($class->academicYear->status->value !== 'ACTIVE' || $semester?->status?->value !== 'ACTIVE') {
                throw ValidationException::withMessages(['decision' => 'Keputusan hanya dapat ditetapkan pada semester Genap aktif.']);
            }
            $batch = AcademicDecisionBatch::query()->where('academic_year_id', $class->academic_year_id)
                ->where('class_id', $class->id)->lockForUpdate()->first();
            if ($batch?->status === 'FINALIZED') {
                throw ValidationException::withMessages(['decision' => 'Keputusan kelas sudah ditetapkan.']);
            }
            $enrollments = $class->enrollments()->where('enrollment_status', 'ACTIVE')->lockForUpdate()->get();
            $decisions = AcademicDecision::query()->where('academic_year_id', $class->academic_year_id)
                ->where('class_id', $class->id)->lockForUpdate()->get()->keyBy('student_id');
            $missing = $enrollments->filter(fn (Enrollment $enrollment): bool => ! $this->current($enrollment, $decisions->get($enrollment->student_id)))->count();
            if ($missing > 0) {
                throw ValidationException::withMessages(['decision' => "Masih ada {$missing} siswa yang belum memiliki keputusan."]);
            }
            $batch = $batch ?? new AcademicDecisionBatch;
            $batch->fill([
                'institution_id' => $actor->institution_id,
                'academic_year_id' => $class->academic_year_id,
                'semester_id' => $semester->id,
                'class_id' => $class->id,
                'status' => 'FINALIZED',
                'finalized_by' => $actor->id,
                'finalized_at' => now(),
                'revision' => ((int) ($batch->revision ?? 0)) + 1,
            ])->save();
            AuditLog::query()->create([
                'institution_id' => $actor->institution_id,
                'user_id' => $actor->id,
                'action' => 'academic_decision.finalized',
                'entity_type' => AcademicDecisionBatch::class,
                'entity_id' => $batch->id,
                'new_value_json' => ['class_id' => $class->id, 'academic_year_id' => $class->academic_year_id, 'students' => $enrollments->count()],
            ]);
        });
    }

    public function reopen(User $actor, AcademicClass $class, string $reason): void
    {
        abort_unless($actor->isActive() && $actor->hasRole('ADMIN'), 403);
        abort_unless($class->academicYear->institution_id === $actor->institution_id, 404);

        DB::transaction(function () use ($actor, $class, $reason): void {
            Institution::query()->whereKey($actor->institution_id)->lockForUpdate()->firstOrFail();
            $class = AcademicClass::query()->with('academicYear')->lockForUpdate()->findOrFail($class->id);
            $batch = AcademicDecisionBatch::query()->where('academic_year_id', $class->academic_year_id)
                ->where('class_id', $class->id)->lockForUpdate()->firstOrFail();
            if ($class->academicYear->status->value === 'CLOSED') {
                throw ValidationException::withMessages(['reason' => 'Keputusan pada tahun pelajaran yang sudah ditutup tidak dapat dibuka.']);
            }
            if ($batch->status !== 'FINALIZED') {
                throw ValidationException::withMessages(['reason' => 'Kelas belum memiliki keputusan akhir yang terkunci.']);
            }
            $before = $batch->getAttributes();
            $batch->update(['status' => 'DRAFT', 'reopened_by' => $actor->id, 'reopened_at' => now(), 'reopen_reason' => $reason,
                'revision' => $batch->revision + 1]);
            AuditLog::query()->create([
                'institution_id' => $actor->institution_id,
                'user_id' => $actor->id,
                'action' => 'academic_decision.reopened',
                'entity_type' => AcademicDecisionBatch::class,
                'entity_id' => $batch->id,
                'old_value_json' => $before,
                'new_value_json' => $batch->getAttributes(),
            ]);
        });
    }
}
