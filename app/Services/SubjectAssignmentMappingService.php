<?php

namespace App\Services;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicClass;
use App\Models\ReportCard;
use App\Models\ReportGrade;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SubjectAssignmentMappingService
{
    /**
     * @param  array<int, int|string>  $assignmentIds
     * @return Collection<int, SubjectAssignment>
     */
    public function reorder(AcademicClass $academicClass, array $assignmentIds, User $actor): Collection
    {
        return DB::transaction(function () use ($academicClass, $assignmentIds, $actor): Collection {
            $lockedClass = $this->lockClass($academicClass);
            $this->assertAdmin($actor, $lockedClass);
            $this->assertOpenPeriod($lockedClass);

            $assignments = SubjectAssignment::query()
                ->where('class_id', $lockedClass->getKey())
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (SubjectAssignment $assignment): string => (string) $assignment->getKey());

            $normalizedIds = array_map('intval', array_values($assignmentIds));
            if (count($normalizedIds) !== $assignments->count() || count(array_unique($normalizedIds)) !== count($normalizedIds)) {
                throw ValidationException::withMessages([
                    'assignment_ids' => 'Semua penugasan pada kelas harus disertakan tepat satu kali saat mengatur urutan.',
                ]);
            }

            foreach ($normalizedIds as $assignmentId) {
                $assignment = $assignments->get((string) $assignmentId);
                if ($assignment === null) {
                    throw ValidationException::withMessages([
                        'assignment_ids' => 'Terdapat penugasan yang bukan milik kelas ini.',
                    ]);
                }

                $this->assertUnlockedAssignment($assignment);
            }

            foreach ($normalizedIds as $position => $assignmentId) {
                $assignments->get((string) $assignmentId)->update(['display_order' => $position + 1]);
            }

            return SubjectAssignment::query()
                ->where('class_id', $lockedClass->getKey())
                ->with(['subject', 'teacher.staffProfile'])
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();
        });
    }

    /**
     * @param  array<int, int|string>  $classIds
     * @param  array<int, int|string>  $subjectIds
     * @return array{created: int, skipped: int, classes: int, subjects: int}
     */
    public function bulkAdd(
        array $classIds,
        array $subjectIds,
        int $teacherId,
        int $minimumScore,
        User $actor,
    ): array {
        return DB::transaction(function () use ($classIds, $subjectIds, $teacherId, $minimumScore, $actor): array {
            $classIds = array_values(array_unique(array_map('intval', $classIds)));
            $subjectIds = array_values(array_unique(array_map('intval', $subjectIds)));

            if ($classIds === [] || $subjectIds === []) {
                throw ValidationException::withMessages([
                    'class_ids' => 'Pilih sedikitnya satu kelas dan satu mata pelajaran.',
                ]);
            }

            $classes = AcademicClass::query()
                ->whereKey($classIds)
                ->with(['academicYear', 'program'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($classes->count() !== count($classIds)) {
                throw ValidationException::withMessages([
                    'class_ids' => 'Sebagian kelas tidak ditemukan.',
                ]);
            }

            $this->assertAdmin($actor, $classes->first());
            $institutionIds = $classes->map(fn (AcademicClass $class): ?int => $class->program?->institution_id)->unique()->values();
            if ($institutionIds->count() !== 1 || $institutionIds->first() !== $actor->institution_id) {
                throw ValidationException::withMessages(['class_ids' => 'Kelas harus berasal dari institusi pengguna yang sama.']);
            }

            $academicYearIds = $classes->pluck('academic_year_id')->unique();
            if ($academicYearIds->count() !== 1) {
                throw ValidationException::withMessages(['class_ids' => 'Bulk mapping hanya dapat dilakukan pada kelas dalam satu tahun pelajaran.']);
            }

            foreach ($classes as $class) {
                $this->assertOpenPeriod($class);
            }

            $subjects = Subject::query()
                ->whereKey($subjectIds)
                ->where('institution_id', $actor->institution_id)
                ->where('active', true)
                ->get()
                ->keyBy('id');
            if ($subjects->count() !== count($subjectIds)) {
                throw ValidationException::withMessages(['subject_ids' => 'Semua mata pelajaran harus aktif dan berasal dari institusi yang sama.']);
            }

            $teacher = $this->findTeacher($teacherId, $actor);
            if ($minimumScore < 0 || $minimumScore > 100) {
                throw ValidationException::withMessages(['minimum_score' => 'KKM harus berada pada rentang 0 sampai 100.']);
            }

            $created = 0;
            $skipped = 0;
            foreach ($classes as $class) {
                $existing = SubjectAssignment::query()
                    ->where('class_id', $class->getKey())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('subject_id');
                $nextOrder = ((int) $existing->max('display_order')) + 1;

                foreach ($subjectIds as $subjectId) {
                    if ($existing->has($subjectId)) {
                        $skipped++;

                        continue;
                    }

                    $assignment = SubjectAssignment::query()->create([
                        'class_id' => $class->getKey(),
                        'subject_id' => $subjectId,
                        'teacher_id' => $teacher->getKey(),
                        'minimum_score' => $minimumScore,
                        'display_order' => $nextOrder++,
                        'active' => true,
                    ]);
                    $existing->put($subjectId, $assignment);
                    $created++;
                }
            }

            return [
                'created' => $created,
                'skipped' => $skipped,
                'classes' => $classes->count(),
                'subjects' => count($subjectIds),
            ];
        });
    }

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function copyConfiguration(AcademicClass $source, AcademicClass $target, User $actor): array
    {
        return DB::transaction(function () use ($source, $target, $actor): array {
            if ($source->is($target)) {
                throw ValidationException::withMessages(['source_class_id' => 'Kelas sumber dan kelas tujuan harus berbeda.']);
            }

            $classes = collect([$source, $target])->sortBy(fn (AcademicClass $class): int => $class->getKey());
            $lockedClasses = $classes->mapWithKeys(fn (AcademicClass $class): array => [
                $class->getKey() => $this->lockClass($class),
            ]);
            $lockedSource = $lockedClasses->get($source->getKey());
            $lockedTarget = $lockedClasses->get($target->getKey());

            $this->assertAdmin($actor, $lockedTarget);
            $this->assertSameInstitution($lockedSource, $lockedTarget, $actor);
            $this->assertOpenPeriod($lockedSource);
            $this->assertOpenPeriod($lockedTarget);

            if ($lockedSource->academic_year_id !== $lockedTarget->academic_year_id) {
                throw ValidationException::withMessages(['source_class_id' => 'Konfigurasi hanya dapat disalin dalam tahun pelajaran yang sama.']);
            }

            $sourceAssignments = SubjectAssignment::query()
                ->where('class_id', $lockedSource->getKey())
                ->with('teacher')
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();
            $targetAssignments = SubjectAssignment::query()
                ->where('class_id', $lockedTarget->getKey())
                ->lockForUpdate()
                ->get()
                ->keyBy('subject_id');

            $created = 0;
            $updated = 0;
            $skipped = 0;
            foreach ($sourceAssignments as $sourceAssignment) {
                $teacher = $this->findTeacher((int) $sourceAssignment->teacher_id, $actor);
                $attributes = [
                    'teacher_id' => $teacher->getKey(),
                    'minimum_score' => $sourceAssignment->minimum_score,
                    'display_order' => $sourceAssignment->display_order,
                    'active' => $sourceAssignment->active,
                ];
                $targetAssignment = $targetAssignments->get($sourceAssignment->subject_id);

                if ($targetAssignment === null) {
                    $targetAssignment = SubjectAssignment::query()->create([
                        'class_id' => $lockedTarget->getKey(),
                        'subject_id' => $sourceAssignment->subject_id,
                        ...$attributes,
                    ]);
                    $targetAssignments->put($sourceAssignment->subject_id, $targetAssignment);
                    $created++;

                    continue;
                }

                if ($targetAssignment->only(array_keys($attributes)) === $attributes) {
                    $skipped++;

                    continue;
                }

                $this->assertUnlockedAssignment($targetAssignment);
                $targetAssignment->update($attributes);
                $updated++;
            }

            return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
        });
    }

    /**
     * @param  array<int, int|string>  $assignmentIds
     */
    public function bulkAssignTeacher(array $assignmentIds, int $teacherId, User $actor): int
    {
        return DB::transaction(function () use ($assignmentIds, $teacherId, $actor): int {
            $assignmentIds = array_values(array_unique(array_map('intval', $assignmentIds)));
            if ($assignmentIds === []) {
                throw ValidationException::withMessages(['assignment_ids' => 'Pilih sedikitnya satu penugasan.']);
            }

            $assignments = SubjectAssignment::query()
                ->whereKey($assignmentIds)
                ->with(['academicClass.academicYear', 'academicClass.program'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            if ($assignments->count() !== count($assignmentIds)) {
                throw ValidationException::withMessages(['assignment_ids' => 'Sebagian penugasan tidak ditemukan.']);
            }

            $this->assertAdmin($actor, $assignments->first()->academicClass);
            $teacher = $this->findTeacher($teacherId, $actor);
            foreach ($assignments as $assignment) {
                $this->assertClassInstitution($assignment->academicClass, $actor);
                $this->assertOpenPeriod($assignment->academicClass);
                $this->assertUnlockedAssignment($assignment);
            }

            foreach ($assignments as $assignment) {
                $assignment->update(['teacher_id' => $teacher->getKey()]);
            }

            return $assignments->count();
        });
    }

    private function lockClass(AcademicClass $academicClass): AcademicClass
    {
        return AcademicClass::query()
            ->with(['academicYear', 'program'])
            ->whereKey($academicClass->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertAdmin(User $actor, AcademicClass $academicClass): void
    {
        if (! $actor->isActive() || ! $actor->hasRole('ADMIN')) {
            throw ValidationException::withMessages(['mapping' => 'Hanya Admin aktif yang dapat mengubah mapping mata pelajaran.']);
        }

        $this->assertClassInstitution($academicClass, $actor);
    }

    private function assertClassInstitution(AcademicClass $academicClass, User $actor): void
    {
        if ($academicClass->program?->institution_id !== $actor->institution_id) {
            throw ValidationException::withMessages(['mapping' => 'Kelas bukan bagian dari institusi pengguna.']);
        }
    }

    private function assertSameInstitution(AcademicClass $source, AcademicClass $target, User $actor): void
    {
        $this->assertClassInstitution($source, $actor);
        $this->assertClassInstitution($target, $actor);
    }

    private function assertOpenPeriod(AcademicClass $academicClass): void
    {
        if ($academicClass->academicYear?->status === AcademicYearStatus::Closed) {
            throw ValidationException::withMessages(['mapping' => 'Periode akademik sudah ditutup dan tidak menerima perubahan mapping.']);
        }
    }

    private function assertUnlockedAssignment(SubjectAssignment $assignment): void
    {
        $hasLockedGrades = ReportGrade::query()
            ->where('subject_assignment_id', $assignment->getKey())
            ->whereIn('report_card_id', ReportCard::query()->select('id')->where('status', 'LOCKED'))
            ->exists();

        if ($hasLockedGrades) {
            throw ValidationException::withMessages([
                'mapping' => 'Penugasan tidak dapat diubah karena sudah memiliki nilai pada rapor terkunci.',
            ]);
        }
    }

    private function findTeacher(int $teacherId, User $actor): User
    {
        $teacher = User::query()
            ->whereKey($teacherId)
            ->where('institution_id', $actor->institution_id)
            ->where('status', 'ACTIVE')
            ->whereHas('roles', fn ($roles) => $roles->where('code', 'GURU'))
            ->first();

        if ($teacher === null) {
            throw ValidationException::withMessages(['teacher_id' => 'Guru harus aktif dan berasal dari institusi yang sama.']);
        }

        return $teacher;
    }
}
