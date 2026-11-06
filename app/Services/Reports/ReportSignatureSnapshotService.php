<?php

namespace App\Services\Reports;

use App\Enums\ReportSignerType;
use App\Enums\SignatorySource;
use App\Exceptions\MissingReportSignatory;
use App\Models\InstitutionSignatorySetting;
use App\Models\ReportCard;
use App\Models\ReportSignature;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class ReportSignatureSnapshotService
{
    public function snapshotForLockedReportCard(ReportCard $reportCard, ?CarbonInterface $signedAt = null): Collection
    {
        if ($reportCard->status !== 'LOCKED') {
            throw new MissingReportSignatory('Snapshot penandatangan hanya dapat dibuat untuk rapor yang sudah dikunci.');
        }

        $reportCard->loadMissing([
            'enrollment.student',
            'enrollment.academicClass.program',
            'enrollment.academicClass.homeroomTeacher.staffProfile',
        ]);

        $student = $reportCard->enrollment?->student;
        $academicClass = $reportCard->enrollment?->academicClass;
        $institutionId = $student?->institution_id ?? $academicClass?->program?->institution_id;

        if ($institutionId === null) {
            throw new MissingReportSignatory('Rapor belum memiliki PKBM yang dapat digunakan untuk mencari penandatangan.');
        }

        $snapshots = [
            $this->headmasterSnapshot($institutionId),
            $this->homeroomTeacherSnapshot($academicClass?->homeroomTeacher),
        ];

        if (filled($student?->guardian_name)) {
            $snapshots[] = [
                'signer_type' => ReportSignerType::ParentGuardian->value,
                'user_id' => null,
                'signer_name_snapshot' => $student->guardian_name,
                'signer_title_snapshot' => 'Orang Tua/Wali',
                'signer_employee_number_snapshot' => null,
            ];
        }

        $signedAt ??= $reportCard->locked_at ?? now();

        return DB::transaction(function () use ($reportCard, $snapshots, $signedAt): Collection {
            $signatures = new Collection;

            foreach ($snapshots as $snapshot) {
                $signatures->push(ReportSignature::query()->firstOrCreate(
                    [
                        'report_card_id' => $reportCard->getKey(),
                        'signer_type' => $snapshot['signer_type'],
                    ],
                    [
                        'user_id' => $snapshot['user_id'],
                        'signer_name_snapshot' => $snapshot['signer_name_snapshot'],
                        'signer_title_snapshot' => $snapshot['signer_title_snapshot'],
                        'signer_employee_number_snapshot' => $snapshot['signer_employee_number_snapshot'],
                        'signed_at' => $signedAt,
                    ],
                ));
            }

            return $signatures;
        });
    }

    /**
     * @return array{signer_type: string, user_id: int|null, signer_name_snapshot: string, signer_title_snapshot: string|null, signer_employee_number_snapshot: string|null}
     */
    private function headmasterSnapshot(int $institutionId): array
    {
        $setting = InstitutionSignatorySetting::query()
            ->with(['headmasterUser.staffProfile'])
            ->where('institution_id', $institutionId)
            ->first();

        if ($setting === null) {
            throw new MissingReportSignatory('Pengaturan Kepala PKBM belum tersedia.');
        }

        if ($setting->headmaster_source === SignatorySource::Manual) {
            if (blank($setting->headmaster_name)) {
                throw new MissingReportSignatory('Nama Kepala PKBM manual belum diisi.');
            }

            return [
                'signer_type' => ReportSignerType::HeadOfPkbm->value,
                'user_id' => null,
                'signer_name_snapshot' => $setting->headmaster_name,
                'signer_title_snapshot' => $setting->headmaster_title ?: 'Kepala PKBM',
                'signer_employee_number_snapshot' => $setting->headmaster_employee_number,
            ];
        }

        $user = $setting->headmasterUser;

        if (! $this->isActiveHeadmaster($user, $institutionId)) {
            throw new MissingReportSignatory('Akun Kepala PKBM pada pengaturan tidak valid atau tidak aktif.');
        }

        return [
            'signer_type' => ReportSignerType::HeadOfPkbm->value,
            'user_id' => $user->getKey(),
            'signer_name_snapshot' => $user->name,
            'signer_title_snapshot' => $user->staffProfile?->title ?: 'Kepala PKBM',
            'signer_employee_number_snapshot' => $user->staffProfile?->employee_number,
        ];
    }

    /**
     * @return array{signer_type: string, user_id: int|null, signer_name_snapshot: string, signer_title_snapshot: string|null, signer_employee_number_snapshot: string|null}
     */
    private function homeroomTeacherSnapshot(?User $teacher): array
    {
        if ($teacher === null || ! $teacher->isActive() || ! $teacher->hasRole('GURU')) {
            throw new MissingReportSignatory('Akun Guru wali kelas belum ditetapkan atau tidak aktif.');
        }

        return [
            'signer_type' => ReportSignerType::HomeroomTeacher->value,
            'user_id' => $teacher->getKey(),
            'signer_name_snapshot' => $teacher->name,
            'signer_title_snapshot' => $teacher->staffProfile?->title ?: 'Guru',
            'signer_employee_number_snapshot' => $teacher->staffProfile?->employee_number,
        ];
    }

    private function isActiveHeadmaster(?User $user, int $institutionId): bool
    {
        return $user !== null
            && $user->institution_id === $institutionId
            && $user->isActive()
            && $user->hasRole('KEPSEK');
    }
}
