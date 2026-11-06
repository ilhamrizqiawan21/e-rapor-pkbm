<?php

namespace App\Services;

use App\Enums\AcademicYearStatus;
use App\Enums\SemesterStatus;
use App\Models\AcademicYear;
use App\Models\ReportCard;
use App\Models\Semester;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AcademicPeriodService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createAcademicYear(int $institutionId, array $data): AcademicYear
    {
        return DB::transaction(function () use ($institutionId, $data): AcademicYear {
            $this->lockAcademicYears($institutionId);
            $this->assertSingleActiveAcademicYear($institutionId, $data['status']);
            $this->assertClosureConfirmed($data['status'], $data);

            return AcademicYear::query()->create([
                'institution_id' => $institutionId,
                ...Arr::only($data, ['name', 'start_date', 'end_date', 'status']),
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAcademicYear(AcademicYear $academicYear, array $data): AcademicYear
    {
        return DB::transaction(function () use ($academicYear, $data): AcademicYear {
            $academicYear->refresh();
            $this->lockAcademicYears($academicYear->institution_id);
            $this->assertSingleActiveAcademicYear($academicYear->institution_id, $data['status'], $academicYear);
            $this->assertClosureConfirmed($data['status'], $data);

            $academicYear->update(Arr::only($data, ['name', 'start_date', 'end_date', 'status']));

            return $academicYear->refresh();
        });
    }

    public function deleteAcademicYear(AcademicYear $academicYear): void
    {
        if ($academicYear->semesters()->exists() || $academicYear->classes()->exists()) {
            throw ValidationException::withMessages([
                'academic_year' => 'Tahun pelajaran tidak dapat dihapus karena sudah memiliki semester atau kelas.',
            ]);
        }

        $academicYear->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSemester(AcademicYear $academicYear, array $data): Semester
    {
        return DB::transaction(function () use ($academicYear, $data): Semester {
            $academicYear->refresh();
            $this->assertAcademicYearOpen($academicYear);
            $this->assertSingleActiveSemester($academicYear, $data['status']);
            $this->assertUniqueSequence($academicYear, (int) $data['sequence']);
            $this->assertClosureConfirmed($data['status'], $data);

            return $academicYear->semesters()->create(Arr::only($data, [
                'code',
                'name',
                'sequence',
                'status',
            ]));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSemester(Semester $semester, array $data): Semester
    {
        return DB::transaction(function () use ($semester, $data): Semester {
            $semester->load('academicYear');
            $this->assertSemesterOpen($semester);
            $this->assertSingleActiveSemester($semester->academicYear, $data['status'], $semester);
            $this->assertUniqueSequence($semester->academicYear, (int) $data['sequence'], $semester);
            $this->assertClosureConfirmed($data['status'], $data);

            $semester->update(Arr::only($data, ['code', 'name', 'sequence', 'status']));

            return $semester->refresh();
        });
    }

    public function deleteSemester(Semester $semester): void
    {
        $semester->load('academicYear');
        $this->assertSemesterOpen($semester);

        if (ReportCard::query()->where('semester_id', $semester->getKey())->exists()) {
            throw ValidationException::withMessages([
                'semester' => 'Semester tidak dapat dihapus karena sudah memiliki rapor.',
            ]);
        }

        $semester->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertClosureConfirmed(string $status, array $data): void
    {
        if ($status === AcademicYearStatus::Closed->value || $status === SemesterStatus::Closed->value) {
            if (! filter_var($data['confirm_closure'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                throw ValidationException::withMessages([
                    'confirm_closure' => 'Konfirmasi penutupan wajib dicentang.',
                ]);
            }
        }
    }

    private function assertAcademicYearOpen(AcademicYear $academicYear): void
    {
        if ($academicYear->status === AcademicYearStatus::Closed) {
            throw ValidationException::withMessages([
                'academic_year' => 'Tahun pelajaran yang ditutup tidak menerima perubahan semester.',
            ]);
        }
    }

    private function assertSemesterOpen(Semester $semester): void
    {
        if ($semester->status === SemesterStatus::Closed) {
            throw ValidationException::withMessages([
                'semester' => 'Semester yang ditutup tidak menerima perubahan.',
            ]);
        }

        $this->assertAcademicYearOpen($semester->academicYear);
    }

    private function assertSingleActiveAcademicYear(int $institutionId, string $status, ?AcademicYear $except = null): void
    {
        if ($status !== AcademicYearStatus::Active->value) {
            return;
        }

        $query = AcademicYear::query()
            ->where('institution_id', $institutionId)
            ->where('status', AcademicYearStatus::Active->value);

        if ($except !== null) {
            $query->whereKeyNot($except->getKey());
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya satu tahun pelajaran yang dapat berstatus Aktif pada satu institusi.',
            ]);
        }
    }

    private function assertSingleActiveSemester(AcademicYear $academicYear, string $status, ?Semester $except = null): void
    {
        if ($status !== SemesterStatus::Active->value) {
            return;
        }

        if ($academicYear->status !== AcademicYearStatus::Active) {
            throw ValidationException::withMessages([
                'status' => 'Semester hanya dapat diaktifkan ketika tahun pelajaran berstatus Aktif.',
            ]);
        }

        $query = $academicYear->semesters()
            ->where('status', SemesterStatus::Active->value);

        if ($except !== null) {
            $query->whereKeyNot($except->getKey());
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya satu semester yang dapat berstatus Aktif pada satu tahun pelajaran.',
            ]);
        }
    }

    private function assertUniqueSequence(AcademicYear $academicYear, int $sequence, ?Semester $except = null): void
    {
        $query = $academicYear->semesters()->where('sequence', $sequence);

        if ($except !== null) {
            $query->whereKeyNot($except->getKey());
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'sequence' => 'Urutan semester sudah digunakan pada tahun pelajaran ini.',
            ]);
        }
    }

    private function lockAcademicYears(int $institutionId): void
    {
        AcademicYear::query()
            ->where('institution_id', $institutionId)
            ->lockForUpdate()
            ->get();
    }
}
