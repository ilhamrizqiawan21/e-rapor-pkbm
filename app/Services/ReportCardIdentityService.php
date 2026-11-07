<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ReportCard;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Handles report identity fields and snapshots used by printable output.
 */
final class ReportCardIdentityService
{
    /**
     * @return array<string, mixed>
     */
    public function context(ReportCard $reportCard): array
    {
        $reportCard->loadMissing($this->identityRelations());

        $storedSnapshot = $reportCard->getAttribute('identity_snapshot');
        if (is_string($storedSnapshot)) {
            $storedSnapshot = json_decode($storedSnapshot, true);
        }

        $archived = $reportCard->semester?->status?->value === 'CLOSED' || $reportCard->semester?->academicYear?->status?->value === 'CLOSED';
        $identity = ($reportCard->status === 'LOCKED' || $archived) && is_array($storedSnapshot)
            ? $storedSnapshot
            : $this->liveIdentity($reportCard);

        return [
            ...$identity,
            'document_number' => $reportCard->document_number,
            'is_locked' => $reportCard->status === 'LOCKED',
        ];
    }

    /**
     * Capture the values used by the printed report at the moment of locking.
     *
     * @return array<string, mixed>
     */
    public function snapshot(ReportCard $reportCard): array
    {
        $reportCard->loadMissing($this->identityRelations());

        return [
            ...$this->liveIdentity($reportCard),
            'document_number' => $reportCard->document_number,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    public function updateDocumentNumber(ReportCard $reportCard, ?string $documentNumber, User $actor): ReportCard
    {
        // The document number is institution-owned metadata and is changed
        // through the report-card workflow, not arbitrary mass assignment.
        Gate::forUser($actor)->authorize('manageCompleteness', $reportCard);

        return DB::transaction(function () use ($reportCard, $documentNumber, $actor): ReportCard {
            $locked = ReportCard::query()->lockForUpdate()->findOrFail($reportCard->getKey());
            Gate::forUser($actor)->authorize('manageCompleteness', $locked);

            $oldDocumentNumber = $locked->document_number;
            $locked->forceFill(['document_number' => $documentNumber])->save();

            $locked->loadMissing('enrollment.academicClass.program');
            AuditLog::query()->create([
                'institution_id' => $locked->enrollment->academicClass->program->institution_id,
                'report_card_id' => $locked->getKey(),
                'user_id' => $actor->getKey(),
                'action' => 'report_card.identity_updated',
                'entity_type' => ReportCard::class,
                'entity_id' => $locked->getKey(),
                'old_value_json' => ['document_number' => $oldDocumentNumber],
                'new_value_json' => ['document_number' => $documentNumber],
            ]);

            return $locked->refresh();
        });
    }

    /**
     * @return array<int, string>
     */
    private function identityRelations(): array
    {
        return [
            'enrollment.student.institution',
            'enrollment.academicClass.level',
            'enrollment.academicClass.program.institution',
            'enrollment.academicClass.academicYear',
            'semester.academicYear.institution',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function liveIdentity(ReportCard $reportCard): array
    {
        $student = $reportCard->enrollment?->student;
        $academicClass = $reportCard->enrollment?->academicClass;
        $program = $academicClass?->program;
        $level = $academicClass?->level;
        $semester = $reportCard->semester;
        $academicYear = $semester?->academicYear ?? $academicClass?->academicYear;
        $institution = $program?->institution ?? $student?->institution ?? $semester?->academicYear?->institution;

        return [
            'student' => [
                'name' => $student?->full_name,
                'student_number' => $student?->student_number,
                'nisn' => $student?->national_student_number,
                'gender' => $student?->gender,
                'birth_place' => $student?->birth_place,
                'birth_date' => $student?->birth_date?->format('d/m/Y'),
                'address' => $student?->address,
                'guardian_name' => $student?->guardian_name,
            ],
            'class' => [
                'name' => $academicClass?->name,
                'code' => $academicClass?->code,
                'level' => $level?->name,
                'program' => $program?->name,
            ],
            'semester' => [
                'code' => $semester?->code?->value ?? $semester?->code,
                'name' => $semester?->name,
            ],
            'academic_year' => [
                'name' => $academicYear?->name,
            ],
            'institution' => [
                'name' => $institution?->name,
                'npsn' => $institution?->npsn,
                'address' => $institution?->address,
                'phone' => $institution?->phone,
                'email' => $institution?->email,
                'postal_code' => $institution?->postal_code,
                'permit_number' => $institution?->permit_number,
            ],
        ];
    }
}
