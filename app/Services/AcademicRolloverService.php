<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademicDecision;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\PeriodPreparation;
use App\Models\ReportCard;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\Reports\ReportCardPdfDataService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AcademicRolloverService
{
    public function __construct(private PeriodReadinessService $readiness, private ReportCardIdentityService $identity, private ReportCardPdfDataService $pdf) {}

    public function authorize(User $actor, AcademicYear $source, bool $write = true): void
    {
        abort_unless($actor->isActive() && $actor->hasAnyRole($write ? ['ADMIN'] : ['ADMIN', 'KEPSEK']), 403);
        abort_unless($source->institution_id === $actor->institution_id, 404);
    }

    public function preparation(AcademicYear $source): ?PeriodPreparation
    {
        return PeriodPreparation::query()->where('source_year_id', $source->id)->where('kind', 'YEAR')->with(['targetYear.semesters', 'placements'])->first();
    }

    public function suggestedYear(AcademicYear $source): array
    {
        $start = $source->end_date?->copy()->addDay() ?? now()->startOfYear()->addMonths(6);
        $end = $start->copy()->addYear()->subDay();

        return ['name' => $start->year.'/'.$end->year, 'start_date' => $start->toDateString(), 'end_date' => $end->toDateString()];
    }

    /** @param array<string, mixed> $data */
    public function prepare(User $actor, AcademicYear $source, array $data): PeriodPreparation
    {
        $this->authorize($actor, $source);

        return DB::transaction(function () use ($actor, $source, $data): PeriodPreparation {
            $this->lock($actor, $source);
            $this->assertGenap($source);
            $preparation = $this->preparation($source);
            $this->check(! $preparation || $preparation->status === 'DRAFT', 'Persiapan ini sudah selesai.');
            $this->check((int) ($data['revision'] ?? 0) === (int) ($preparation?->revision ?? 0), 'Persiapan berubah. Muat ulang halaman.');
            $target = $preparation?->targetYear ?? AcademicYear::query()->where('institution_id', $actor->institution_id)->where('name', $data['name'])->first();
            $this->check(! $target || ($target->id !== $source->id && $target->status->value === 'DRAFT'), 'Nama tahun tujuan sudah digunakan oleh periode aktif atau arsip.');
            $this->check(! $target || ! PeriodPreparation::query()->where('target_year_id', $target->id)->where('source_year_id', '!=', $source->id)->exists(), 'Tahun tujuan sudah dipakai persiapan lain.');
            $this->check($source->end_date && $data['start_date'] > $source->end_date->toDateString(), 'Awal tahun tujuan harus setelah akhir tahun asal.');
            if ($target) {
                $this->check(! ReportCard::query()->whereHas('semester', fn ($q) => $q->where('academic_year_id', $target->id))->exists(), 'Tahun tujuan sudah memiliki rapor.');
                $this->check(! AcademicYear::query()->where('institution_id', $actor->institution_id)->where('name', $data['name'])->whereKeyNot($target->id)->exists(), 'Nama tahun pelajaran sudah digunakan.');
                $target->update(collect($data)->only(['name', 'start_date', 'end_date'])->all());
            } else {
                $target = AcademicYear::query()->create(['institution_id' => $actor->institution_id, 'status' => 'DRAFT', ...collect($data)->only(['name', 'start_date', 'end_date'])->all()]);
            }
            foreach (['GANJIL' => 1, 'GENAP' => 2] as $code => $sequence) {
                $semester = $target->semesters()->firstOrCreate(['code' => $code], ['name' => 'Semester '.ucfirst(strtolower($code)), 'sequence' => $sequence, 'status' => 'DRAFT']);
                $this->check($semester->status->value === 'DRAFT', 'Semester tujuan sudah digunakan.');
            }
            $genap = $source->semesters()->where('code', 'GENAP')->firstOrFail();
            $preparation = PeriodPreparation::query()->updateOrCreate(['source_year_id' => $source->id, 'kind' => 'YEAR'],
                ['source_semester_id' => $genap->id, 'target_year_id' => $target->id, 'target_semester_id' => $target->semesters()->where('code', 'GANJIL')->firstOrFail()->id,
                    'prepared_by' => $actor->id, 'revision' => ($preparation?->revision ?? 0) + 1, 'review_hash' => null, 'configuration_reviewed' => false]);
            $this->audit($actor, $target, 'academic_period.prepared', ['preparation_id' => $preparation->id]);

            return $preparation;
        });
    }

    /** @param array<string, mixed> $data */
    public function savePlacements(User $actor, AcademicYear $source, array $data): void
    {
        $this->authorize($actor, $source);
        DB::transaction(function () use ($actor, $source, $data): void {
            $this->lock($actor, $source);
            $this->assertGenap($source);
            $preparation = $this->preparation($source);
            $this->check($preparation && $preparation->status === 'DRAFT', 'Siapkan tahun tujuan terlebih dahulu.');
            $this->check((int) $data['revision'] === $preparation->revision, 'Persiapan berubah. Muat ulang halaman.');
            $active = Enrollment::query()->where('academic_year_id', $source->id)->where('enrollment_status', 'ACTIVE')->get()->keyBy('id');
            $allowedClasses = AcademicClass::query()->where('academic_year_id', $preparation->target_year_id)->where('status', 'ACTIVE')->pluck('id')->all();
            foreach ($data['rows'] ?? [] as $id => $row) {
                $enrollment = $active->get($id);
                abort_unless($enrollment, 404);
                $classId = $row['class_id'] ?? null;
                abort_unless(! $classId || in_array((int) $classId, $allowedClasses, true), 404);
                $preparation->placements()->updateOrCreate(['student_id' => $enrollment->student_id], ['enrollment_id' => $enrollment->id,
                    'class_id' => $classId, 'continuation' => $row['continuation'] ?? null]);
            }
            $preparation->update(['configuration_reviewed' => (bool) ($data['configuration_reviewed'] ?? false), 'revision' => $preparation->revision + 1, 'review_hash' => null]);
            $this->audit($actor, $source, 'academic_period.placements_saved', ['preparation_id' => $preparation->id, 'placements' => $preparation->placements()->get()->toArray()]);
        });
    }

    public function review(User $actor, AcademicYear $source): array
    {
        $this->authorize($actor, $source);

        return DB::transaction(function () use ($actor, $source): array {
            $this->lock($actor, $source);
            $preparation = $this->preparation($source);
            $this->check($preparation && $preparation->status === 'DRAFT', 'Siapkan tahun tujuan terlebih dahulu.');
            $state = $this->readiness->evaluate($source, $preparation);
            $preparation->update(['review_hash' => $state['fingerprint']]);

            return $state;
        });
    }

    public function activate(User $actor, AcademicYear $source, string $fingerprint): void
    {
        $this->authorize($actor, $source);
        DB::transaction(function () use ($actor, $source, $fingerprint): void {
            $this->lock($actor, $source);
            $source->refresh();
            $semester = $source->semesters()->where('status', 'ACTIVE')->first();
            $this->check($source->status->value === 'ACTIVE' && $semester, 'Periode asal sudah tidak aktif.');
            $yearChange = $semester->code->value === 'GENAP';
            $preparation = $yearChange ? $this->preparation($source) : null;
            if ($yearChange) {
                $this->check($preparation && $preparation->status === 'DRAFT' && $preparation->review_hash, 'Periksa ringkasan tahun baru terlebih dahulu.');
                $this->check(hash_equals($preparation->review_hash, $fingerprint), 'Ringkasan tidak sesuai. Periksa ulang persiapan.');
            }
            $state = $this->readiness->evaluate($source, $preparation);
            $this->check(hash_equals($state['fingerprint'], $fingerprint), 'Data berubah setelah diperiksa. Periksa ringkasan terbaru.');
            $this->check($state['ready'], implode(' ', $state['blockers']));
            $this->archive($source, $yearChange ? null : $semester->id);
            if ($yearChange) {
                $target = $preparation->targetYear;
                foreach ($state['rows'] as $row) {
                    $old = $row['enrollment'];
                    $decision = $row['decision'];
                    $finish = $decision->decision === 'GRADUATE' && ((int) $old->academicClass->level->number === 12 || $row['placement']?->continuation === 'FINISH');
                    $old->update(['end_date' => $source->end_date->toDateString(), 'enrollment_status' => $decision->decision === 'GRADUATE' ? 'GRADUATED' : 'COMPLETED']);
                    if ($finish) {
                        $old->student()->update(['status' => 'GRADUATED']);
                    } else {
                        $new = Enrollment::query()->create(['student_id' => $old->student_id, 'class_id' => $row['destination']->id,
                            'academic_year_id' => $target->id, 'start_date' => $target->start_date->toDateString(), 'enrollment_status' => 'ACTIVE',
                            'notes' => 'Peralihan dari '.$source->name.' — '.$decision->label_snapshot]);
                        $new->update(['reporting_enrollment_id' => $new->id]);
                    }
                }
                $source->semesters()->update(['status' => 'CLOSED']);
                $source->update(['status' => 'CLOSED']);
                $target->update(['status' => 'ACTIVE']);
                Semester::query()->whereKey($preparation->target_semester_id)->update(['status' => 'ACTIVE']);
                $preparation->update(['status' => 'COMPLETED', 'completed_at' => now()]);
            } else {
                $targetSemester = $source->semesters()->firstOrCreate(['code' => 'GENAP'], ['name' => 'Semester Genap', 'sequence' => 2, 'status' => 'DRAFT']);
                $semester->update(['status' => 'CLOSED']);
                $targetSemester->update(['status' => 'ACTIVE']);
                PeriodPreparation::query()->create(['source_year_id' => $source->id, 'source_semester_id' => $semester->id,
                    'target_year_id' => $source->id, 'target_semester_id' => $targetSemester->id, 'prepared_by' => $actor->id,
                    'kind' => 'SEMESTER', 'status' => 'COMPLETED', 'completed_at' => now()]);
            }
            $this->audit($actor, $source, 'academic_period.transitioned', ['kind' => $yearChange ? 'YEAR' : 'SEMESTER',
                'target_year_id' => $yearChange ? $preparation->target_year_id : $source->id,
                'students' => collect($state['rows'])->map(fn ($row) => ['student_id' => $row['enrollment']->student_id, 'decision' => $row['decision']?->decision, 'class_id' => $row['destination']?->id])->all()]);
        });
    }

    public function copyClasses(User $actor, AcademicYear $source, AcademicYear $target): void
    {
        $this->authorize($actor, $source);
        abort_unless($target->institution_id === $actor->institution_id, 404);
        DB::transaction(function () use ($actor, $source, $target): void {
            $this->lock($actor, $source);
            $this->assertGenap($source);
            $preparation = $this->preparation($source);
            $target->refresh();
            $this->check($preparation && $preparation->status === 'DRAFT' && $preparation->target_year_id === $target->id && $target->status->value === 'DRAFT', 'Siapkan tahun tujuan Draf terlebih dahulu.');
            foreach ($source->classes()->with('subjectAssignments')->orderBy('id')->get() as $class) {
                $new = AcademicClass::query()->firstOrCreate(['academic_year_id' => $target->id, 'code' => $class->code],
                    ['program_id' => $class->program_id, 'level_id' => $class->level_id, 'name' => $class->name, 'homeroom_teacher_id' => $class->homeroom_teacher_id, 'status' => $class->status]);
                if ($new->wasRecentlyCreated) {
                    foreach ($class->subjectAssignments as $assignment) {
                        $new->subjectAssignments()->create($assignment->only(['subject_id', 'teacher_id', 'minimum_score', 'display_order', 'active']));
                    }
                    if ($new->homeroom_teacher_id) {
                        $new->homeroomAssignments()->create(['teacher_id' => $new->homeroom_teacher_id, 'assigned_by_id' => $actor->id, 'assigned_at' => now(), 'status' => 'ACTIVE']);
                    }
                }
            }
            $preparation->update(['configuration_reviewed' => false, 'review_hash' => null, 'revision' => $preparation->revision + 1]);
            $this->audit($actor, $target, 'academic_period.classes_copied', ['source_id' => $source->id]);
        });
    }

    public function archive(AcademicYear $source, ?int $semesterId = null): void
    {
        $reports = ReportCard::query()->whereHas('semester', fn ($q) => $q->where('academic_year_id', $source->id))
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))->lockForUpdate()->get();
        foreach ($reports as $report) {
            $stored = $report->getAttribute('identity_snapshot');
            $stored = is_string($stored) ? json_decode($stored, true) : $stored;
            if (! isset($stored['archived_output'])) {
                $output = $this->pdf->data($report, false);
                $snapshot = $report->status === 'LOCKED' && is_array($stored) ? $stored : $this->identity->snapshot($report);
                $snapshot['archived_output'] = ['settings' => $output['settings']->getAttributes(), 'signatureSlots' => collect($output['signatureSlots'])->all(),
                    'studentName' => $output['studentName'], 'logoDataUri' => $output['logoDataUri'], 'issuedDate' => $output['issuedDate'],
                    'footerText' => $output['footerText'], 'decisionLabel' => $output['decisionLabel'] ?? null];
                $report->forceFill(['identity_snapshot' => $snapshot])->save();
            }
        }
    }

    private function lock(User $actor, AcademicYear $source): void
    {
        Institution::query()->whereKey($actor->institution_id)->lockForUpdate()->firstOrFail();
        AcademicYear::query()->where('institution_id', $actor->institution_id)->orderBy('id')->lockForUpdate()->get();
        Student::query()->whereIn('id', Enrollment::query()->where('academic_year_id', $source->id)->select('student_id'))->orderBy('id')->lockForUpdate()->get();
        AcademicClass::query()->whereHas('academicYear', fn ($q) => $q->where('institution_id', $actor->institution_id))->orderBy('id')->lockForUpdate()->get();
        Enrollment::query()->where('academic_year_id', $source->id)->orderBy('id')->lockForUpdate()->get();
        AcademicDecision::query()->where('academic_year_id', $source->id)->orderBy('id')->lockForUpdate()->get();
        ReportCard::query()->whereHas('semester', fn ($q) => $q->where('academic_year_id', $source->id))->orderBy('id')->lockForUpdate()->get();
        $source->refresh();
    }

    private function assertGenap(AcademicYear $source): void
    {
        $this->check($source->status->value === 'ACTIVE' && $source->semesters()->where('code', 'GENAP')->where('status', 'ACTIVE')->exists(), 'Persiapan tahun baru hanya tersedia saat semester Genap aktif.');
    }

    private function audit(User $actor, AcademicYear $year, string $action, array $data): void
    {
        AuditLog::query()->create(['institution_id' => $actor->institution_id, 'user_id' => $actor->id, 'action' => $action,
            'entity_type' => AcademicYear::class, 'entity_id' => $year->id, 'new_value_json' => $data]);
    }

    private function check(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['transition' => $message]);
        }
    }
}
