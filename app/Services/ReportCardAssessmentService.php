<?php

namespace App\Services;

use App\Models\AttendanceSummary;
use App\Models\AttitudeAssessment;
use App\Models\AuditLog;
use App\Models\ReportCard;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Persists homeroom attitude and attendance data through editable report-card
 * surfaces and records the resulting operational change.
 */
final class ReportCardAssessmentService
{
    public const ATTITUDE_TYPES = ['SPIRITUAL', 'SOCIAL'];

    public const MAX_ATTENDANCE_DAYS = 366;

    /** @return array{reportCard: ReportCard, attitudes: Collection<int|string, AttitudeAssessment>, attendance: AttendanceSummary} */
    public function surface(ReportCard $reportCard, User $actor): array
    {
        Gate::forUser($actor)->authorize('view', $reportCard);

        $reportCard->load([
            'enrollment.student',
            'enrollment.academicClass.homeroomTeacher',
            'enrollment.academicClass.program',
            'semester',
            'attitudeAssessments',
            'attendanceSummary',
        ]);

        $attendance = $reportCard->attendanceSummary ?? new AttendanceSummary([
            'sick_days' => 0,
            'leave_days' => 0,
            'unexcused_days' => 0,
        ]);

        return [
            'reportCard' => $reportCard,
            'attitudes' => $reportCard->attitudeAssessments->keyBy('attitude_type'),
            'attendance' => $attendance,
        ];
    }

    public function saveAttitude(ReportCard $reportCard, User $actor, string $type, ?string $description): AttitudeAssessment
    {
        Gate::forUser($actor)->authorize('updateAttitude', $reportCard);
        $type = strtoupper(trim($type));

        if (! in_array($type, self::ATTITUDE_TYPES, true)) {
            throw ValidationException::withMessages(['attitude_type' => 'Jenis sikap tidak dikenal.']);
        }

        return DB::transaction(function () use ($reportCard, $actor, $type, $description): AttitudeAssessment {
            $locked = ReportCard::query()->lockForUpdate()->findOrFail($reportCard->getKey());
            Gate::forUser($actor)->authorize('updateAttitude', $locked);

            $assessment = $locked->attitudeAssessments()->updateOrCreate(
                ['attitude_type' => $type],
                ['description' => $this->nullableText($description)],
            );

            $this->audit($locked, $actor, 'report_card.attitude_updated', [
                'attitude_type' => $type,
                'description' => $assessment->description,
            ]);

            return $assessment->refresh();
        });
    }

    /** @param array{sick_days: int, leave_days: int, unexcused_days: int} $days */
    public function saveAttendance(ReportCard $reportCard, User $actor, array $days): AttendanceSummary
    {
        Gate::forUser($actor)->authorize('updateAttendance', $reportCard);
        $this->assertAttendanceDays($days);

        return DB::transaction(function () use ($reportCard, $actor, $days): AttendanceSummary {
            $locked = ReportCard::query()->lockForUpdate()->findOrFail($reportCard->getKey());
            Gate::forUser($actor)->authorize('updateAttendance', $locked);
            $summary = $locked->attendanceSummary()->updateOrCreate([], $days);

            $this->audit($locked, $actor, 'report_card.attendance_updated', $days);

            return $summary->refresh();
        });
    }

    /** @param array<string, mixed> $days */
    private function assertAttendanceDays(array $days): void
    {
        // The component totals describe days within one calendar year; accepting
        // more than 366 would make the report internally inconsistent.
        foreach (['sick_days', 'leave_days', 'unexcused_days'] as $key) {
            $value = $days[$key] ?? null;

            if (! is_int($value) || $value < 0 || $value > self::MAX_ATTENDANCE_DAYS) {
                throw ValidationException::withMessages([
                    $key => 'Jumlah hari harus berupa bilangan bulat antara 0 dan 366.',
                ]);
            }
        }

        if (array_sum($days) > self::MAX_ATTENDANCE_DAYS) {
            throw ValidationException::withMessages([
                'attendance' => 'Total hari ketidakhadiran tidak boleh melebihi 366 hari.',
            ]);
        }
    }

    private function nullableText(?string $text): ?string
    {
        $text = $text === null ? null : trim($text);

        return $text === '' ? null : $text;
    }

    /** @param array<string, mixed> $newValue */
    private function audit(ReportCard $reportCard, User $actor, string $action, array $newValue): void
    {
        $reportCard->loadMissing('enrollment.academicClass.program');

        AuditLog::query()->create([
            'institution_id' => $reportCard->enrollment->academicClass->program->institution_id,
            'report_card_id' => $reportCard->getKey(),
            'user_id' => $actor->getKey(),
            'action' => $action,
            'entity_type' => ReportCard::class,
            'entity_id' => $reportCard->getKey(),
            'new_value_json' => $newValue,
        ]);
    }
}
