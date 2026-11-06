<?php

namespace App\Services;

use App\Enums\AcademicYearStatus;
use App\Enums\RoleCode;
use App\Enums\UserStatus;
use App\Models\AcademicClass;
use App\Models\HomeroomAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Maintains the single homeroom-teacher assignment for an academic class.
 */
final class HomeroomAssignmentService
{
    public function assign(AcademicClass $academicClass, User $teacher, User $assignedBy): HomeroomAssignment
    {
        return $this->setAssignment($academicClass, $teacher, $assignedBy);
    }

    public function reassign(AcademicClass $academicClass, User $teacher, User $assignedBy): HomeroomAssignment
    {
        return $this->setAssignment($academicClass, $teacher, $assignedBy);
    }

    public function remove(AcademicClass $academicClass, User $assignedBy): ?HomeroomAssignment
    {
        return DB::transaction(function () use ($academicClass, $assignedBy): ?HomeroomAssignment {
            $lockedClass = $this->lockClass($academicClass);
            $this->assertOpenPeriod($lockedClass);
            $this->assertAdminActor($assignedBy, $lockedClass);

            $current = HomeroomAssignment::query()
                ->where('academic_class_id', $lockedClass->getKey())
                ->whereNull('ended_at')
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                $lockedClass->forceFill(['homeroom_teacher_id' => null])->save();

                return null;
            }

            $current->forceFill([
                'ended_at' => Carbon::now(),
                'status' => 'ENDED',
            ])->save();
            $lockedClass->forceFill(['homeroom_teacher_id' => null])->save();

            return $current->refresh();
        });
    }

    private function setAssignment(AcademicClass $academicClass, User $teacher, User $assignedBy): HomeroomAssignment
    {
        return DB::transaction(function () use ($academicClass, $teacher, $assignedBy): HomeroomAssignment {
            $lockedClass = $this->lockClass($academicClass);
            $this->assertOpenPeriod($lockedClass);
            $this->assertAdminActor($assignedBy, $lockedClass);
            $this->assertValidTeacher($teacher, $lockedClass);

            $current = HomeroomAssignment::query()
                ->where('academic_class_id', $lockedClass->getKey())
                ->whereNull('ended_at')
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if ($current !== null && $current->teacher_id === $teacher->getKey()) {
                return $current->refresh();
            }

            if ($current !== null) {
                $current->forceFill([
                    'ended_at' => Carbon::now(),
                    'status' => 'ENDED',
                ])->save();
            }

            $assignment = HomeroomAssignment::query()->create([
                'academic_class_id' => $lockedClass->getKey(),
                'teacher_id' => $teacher->getKey(),
                'assigned_by_id' => $assignedBy->getKey(),
                'assigned_at' => Carbon::now(),
                'status' => 'ACTIVE',
            ]);

            $lockedClass->forceFill(['homeroom_teacher_id' => $teacher->getKey()])->save();

            return $assignment->refresh();
        });
    }

    private function lockClass(AcademicClass $academicClass): AcademicClass
    {
        // Assignment changes serialize on the class row to preserve one current
        // homeroom relationship under concurrent Admin actions.
        return AcademicClass::query()
            ->with('academicYear')
            ->whereKey($academicClass->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertOpenPeriod(AcademicClass $academicClass): void
    {
        if ($academicClass->academicYear?->status === AcademicYearStatus::Closed) {
            throw ValidationException::withMessages([
                'academic_year' => 'Tahun pelajaran yang ditutup tidak menerima perubahan wali kelas.',
            ]);
        }
    }

    private function assertValidTeacher(User $teacher, AcademicClass $academicClass): void
    {
        $teacher->loadMissing('roles');

        if ($teacher->institution_id !== $this->institutionId($academicClass)
            || $teacher->status !== UserStatus::Active
            || ! $teacher->hasRole(RoleCode::Guru)) {
            throw ValidationException::withMessages([
                'teacher' => 'Wali kelas harus Guru aktif dari institusi yang sama.',
            ]);
        }
    }

    private function assertAdminActor(User $assignedBy, AcademicClass $academicClass): void
    {
        if ($assignedBy->institution_id !== $this->institutionId($academicClass)
            || $assignedBy->status !== UserStatus::Active
            || ! $assignedBy->hasRole(RoleCode::Admin)) {
            throw ValidationException::withMessages([
                'assigned_by' => 'Penetapan wali kelas hanya dapat dilakukan Admin aktif dari institusi yang sama.',
            ]);
        }
    }

    private function institutionId(AcademicClass $academicClass): int
    {
        return (int) $academicClass->academicYear?->institution_id;
    }
}
