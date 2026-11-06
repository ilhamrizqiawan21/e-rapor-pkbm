<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AuditLog;
use App\Models\ClassTransfer;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Maintains one active placement per student and academic year, including the
 * audit trail required for withdrawals and class transfers.
 */
class EnrollmentPlacementService
{
    public function enroll(
        Student $student,
        AcademicClass $academicClass,
        string $startDate,
        ?string $notes,
        User $actor,
    ): Enrollment {
        return DB::transaction(function () use ($student, $academicClass, $startDate, $notes, $actor): Enrollment {
            $this->ensureAdmin($actor);

            $lockedStudent = Student::query()->whereKey($student)->lockForUpdate()->firstOrFail();
            $lockedClass = AcademicClass::query()->whereKey($academicClass)->lockForUpdate()->firstOrFail();
            $this->ensureSameInstitution($actor, $lockedStudent, $lockedClass);
            $this->ensureOpen($lockedClass);

            $start = CarbonImmutable::parse($startDate);
            abort_if(
                $lockedClass->academicYear()->whereDate('start_date', '>', $start)->exists(),
                422,
                'Tanggal mulai enrollment tidak boleh sebelum awal tahun pelajaran.',
            );

            if (Enrollment::query()
                ->where('student_id', $lockedStudent->getKey())
                ->where('academic_year_id', $lockedClass->academic_year_id)
                ->where('enrollment_status', 'ACTIVE')
                ->lockForUpdate()
                ->exists()) {
                throw ValidationException::withMessages([
                    'student_id' => 'Siswa sudah memiliki enrollment aktif pada tahun pelajaran ini.',
                ]);
            }

            // The reporting enrollment is initialized to the first placement so
            // later transfers can choose a deterministic report source.
            $enrollment = Enrollment::query()->create([
                'student_id' => $lockedStudent->getKey(),
                'class_id' => $lockedClass->getKey(),
                'academic_year_id' => $lockedClass->academic_year_id,
                'start_date' => $start->toDateString(),
                'enrollment_status' => 'ACTIVE',
                'notes' => $notes,
            ]);
            $enrollment->update(['reporting_enrollment_id' => $enrollment->getKey()]);

            return $enrollment->fresh(['student', 'academicClass']);
        });
    }

    public function withdraw(
        Enrollment $enrollment,
        string $endDate,
        string $reason,
        User $actor,
    ): Enrollment {
        return DB::transaction(function () use ($enrollment, $endDate, $reason, $actor): Enrollment {
            $this->ensureAdmin($actor);

            $lockedEnrollment = Enrollment::query()->whereKey($enrollment)->lockForUpdate()->firstOrFail();
            $lockedEnrollment->load(['student', 'academicClass.academicYear']);
            $this->ensureSameInstitution($actor, $lockedEnrollment->student, $lockedEnrollment->academicClass);
            $this->ensureOpen($lockedEnrollment->academicClass);

            if ($lockedEnrollment->enrollment_status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'enrollment' => 'Enrollment ini sudah tidak aktif.',
                ]);
            }

            $end = CarbonImmutable::parse($endDate);
            if ($end->isBefore(CarbonImmutable::parse($lockedEnrollment->start_date))) {
                throw ValidationException::withMessages([
                    'end_date' => 'Tanggal keluar tidak boleh sebelum tanggal mulai.',
                ]);
            }

            $lockedEnrollment->update([
                'end_date' => $end->toDateString(),
                'enrollment_status' => 'WITHDRAWN',
                'notes' => trim(implode("\n", array_filter([
                    $lockedEnrollment->notes,
                    'Dikeluarkan: '.$reason,
                ]))),
            ]);

            $this->audit(
                $actor,
                $lockedEnrollment->student->institution_id,
                'student.enrollment_withdrawn',
                $lockedEnrollment,
                ['enrollment_status' => 'ACTIVE', 'end_date' => null],
                ['enrollment_status' => 'WITHDRAWN', 'end_date' => $end->toDateString(), 'reason' => $reason],
                $reason,
            );

            return $lockedEnrollment->fresh(['student', 'academicClass']);
        });
    }

    public function transfer(
        Enrollment $fromEnrollment,
        AcademicClass $targetClass,
        string $effectiveDate,
        string $reason,
        ?string $notes,
        User $actor,
        int|string|null $reportingEnrollmentId = null,
    ): ClassTransfer {
        return DB::transaction(function () use (
            $fromEnrollment,
            $targetClass,
            $effectiveDate,
            $reason,
            $notes,
            $actor,
            $reportingEnrollmentId,
        ): ClassTransfer {
            $this->ensureAdmin($actor);

            $source = Enrollment::query()->whereKey($fromEnrollment)->lockForUpdate()->firstOrFail();
            $source->load(['student', 'academicClass.academicYear']);
            $target = AcademicClass::query()->whereKey($targetClass)->lockForUpdate()->firstOrFail();
            $this->ensureSameInstitution($actor, $source->student, $source->academicClass);
            $this->ensureSameInstitution($actor, $source->student, $target);
            $this->ensureOpen($source->academicClass);
            $this->ensureOpen($target);

            if ($source->enrollment_status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'enrollment' => 'Hanya enrollment aktif yang dapat dipindahkan.',
                ]);
            }

            if ((int) $source->class_id === (int) $target->getKey()) {
                throw ValidationException::withMessages([
                    'target_class_id' => 'Kelas tujuan harus berbeda dari kelas asal.',
                ]);
            }

            if ((int) $source->academic_year_id !== (int) $target->academic_year_id) {
                throw ValidationException::withMessages([
                    'target_class_id' => 'Kelas tujuan harus berada pada tahun pelajaran yang sama.',
                ]);
            }

            $effective = CarbonImmutable::parse($effectiveDate);
            if ($effective->isBefore(CarbonImmutable::parse($source->start_date))) {
                throw ValidationException::withMessages([
                    'effective_date' => 'Tanggal efektif tidak boleh sebelum tanggal mulai enrollment asal.',
                ]);
            }

            $existingActive = Enrollment::query()
                ->where('student_id', $source->student_id)
                ->where('academic_year_id', $source->academic_year_id)
                ->where('enrollment_status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if ($existingActive !== null && $existingActive->getKey() !== $source->getKey()) {
                throw ValidationException::withMessages([
                    'enrollment' => 'Siswa sudah memiliki enrollment aktif lain pada tahun pelajaran ini.',
                ]);
            }

            $newEnrollment = Enrollment::query()->create([
                'student_id' => $source->student_id,
                'class_id' => $target->getKey(),
                'academic_year_id' => $target->academic_year_id,
                'start_date' => $effective->toDateString(),
                'enrollment_status' => 'ACTIVE',
                'notes' => $notes,
            ]);

            $source->update([
                'end_date' => $effective->toDateString(),
                'enrollment_status' => 'TRANSFERRED',
                'notes' => trim(implode("\n", array_filter([
                    $source->notes,
                    'Dipindahkan ke '.$target->name.': '.$reason,
                ]))),
            ]);

            $reportingId = $reportingEnrollmentId === null || $reportingEnrollmentId === '' || $reportingEnrollmentId === 'target'
                ? $newEnrollment->getKey()
                : (int) $reportingEnrollmentId;

            // Only the two enrollments in this transaction may supply the report;
            // accepting another id would make transfer history ambiguous.
            if (! in_array($reportingId, [$source->getKey(), $newEnrollment->getKey()], true)) {
                throw ValidationException::withMessages([
                    'reporting_enrollment_id' => 'Sumber rapor harus enrollment asal atau enrollment tujuan transfer ini.',
                ]);
            }

            $newEnrollment->update(['reporting_enrollment_id' => $reportingId]);
            $transfer = ClassTransfer::query()->create([
                'student_id' => $source->student_id,
                'from_enrollment_id' => $source->getKey(),
                'to_enrollment_id' => $newEnrollment->getKey(),
                'effective_date' => $effective->toDateString(),
                'reason' => $reason,
                'notes' => $notes,
                'approved_by_admin_id' => $actor->getKey(),
            ]);

            $this->audit(
                $actor,
                $source->student->institution_id,
                'student.class_transferred',
                $transfer,
                ['enrollment_id' => $source->getKey(), 'class_id' => $source->class_id, 'status' => 'ACTIVE'],
                ['enrollment_id' => $newEnrollment->getKey(), 'class_id' => $target->getKey(), 'status' => 'ACTIVE', 'reporting_enrollment_id' => $reportingId],
                $reason,
            );

            return $transfer->fresh(['student', 'fromEnrollment.academicClass', 'toEnrollment.academicClass', 'approvedByAdmin']);
        });
    }

    private function ensureAdmin(User $actor): void
    {
        abort_unless($actor->isActive() && $actor->hasRole('ADMIN'), 403);
    }

    private function ensureSameInstitution(User $actor, Student $student, AcademicClass $academicClass): void
    {
        abort_unless(
            $student->institution_id === $actor->institution_id
                && $academicClass->program()->where('institution_id', $actor->institution_id)->exists(),
            403,
        );
    }

    private function ensureOpen(AcademicClass $academicClass): void
    {
        $academicClass->loadMissing('academicYear');
        $status = $academicClass->academicYear->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;

        abort_if(
            strtoupper($statusValue) === 'CLOSED',
            422,
            'Periode akademik sudah ditutup dan tidak menerima perubahan penempatan siswa.',
        );
    }

    private function audit(
        User $actor,
        int $institutionId,
        string $action,
        Model $entity,
        array $oldValues,
        array $newValues,
        string $reason,
    ): void {
        AuditLog::query()->create([
            'institution_id' => $institutionId,
            'user_id' => $actor->getKey(),
            'action' => $action,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'old_value_json' => $oldValues,
            'new_value_json' => $newValues,
            'reason' => $reason,
        ]);
    }
}
