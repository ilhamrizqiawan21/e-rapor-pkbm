<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Semester;

final class AcademicPeriodContext
{
    /**
     * @return array{academicYear: ?AcademicYear, semester: ?Semester, label: string}
     */
    public function forInstitution(?int $institutionId): array
    {
        if ($institutionId === null) {
            return [
                'academicYear' => null,
                'semester' => null,
                'label' => 'Belum dikonfigurasi',
            ];
        }

        $academicYear = AcademicYear::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'ACTIVE')
            ->with(['semesters' => fn ($query) => $query->where('status', 'ACTIVE')->orderBy('sequence')])
            ->first();
        $semester = $academicYear?->semesters->first();

        return [
            'academicYear' => $academicYear,
            'semester' => $semester,
            'label' => $academicYear === null
                ? 'Belum ada tahun pelajaran aktif'
                : ($semester === null ? $academicYear->name.' · semester belum aktif' : $academicYear->name.' · '.$semester->name),
        ];
    }
}
