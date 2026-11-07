<?php

namespace App\Services;

use App\Models\AttendanceSummary;
use App\Models\AttitudeAssessment;
use App\Models\ReportCard;
use App\Models\ReportGrade;
use Illuminate\Validation\ValidationException;

/**
 * Computes the minimum data set required before a report card can advance.
 */
final class ReportCardCompletenessService
{
    public function __construct(private AcademicDecisionService $decisions) {}

    /**
     * @return array{complete: bool, completed: int, total: int, missing: array<int, array{key: string, label: string, message: string}>, components: array<int, array{key: string, label: string, required: bool, complete: bool, message: string}>}
     */
    public function summary(ReportCard $reportCard): array
    {
        $reportCard->loadMissing([
            'semester.academicYear',
            'enrollment.student',
            'enrollment.academicClass.subjectAssignments',
            'grades',
        ]);

        $attitudes = AttitudeAssessment::query()
            ->where('report_card_id', $reportCard->getKey())
            ->get()
            ->keyBy(fn (AttitudeAssessment $assessment): string => strtoupper($assessment->attitude_type));
        $attendance = AttendanceSummary::query()
            ->where('report_card_id', $reportCard->getKey())
            ->first();

        $expectedAssignments = $reportCard->enrollment?->academicClass?->subjectAssignments
            ->where('active', true)
            ->values() ?? collect();
        $gradesByAssignment = $reportCard->grades->keyBy('subject_assignment_id');

        $components = [
            $this->component(
                'document_number',
                'Nomor dokumen',
                filled($reportCard->document_number),
                'Nomor dokumen rapor belum diisi.',
            ),
            $this->component(
                'attitude_spiritual',
                'Sikap spiritual',
                filled($attitudes->get('SPIRITUAL')?->description),
                'Deskripsi sikap spiritual belum diisi.',
            ),
            $this->component(
                'attitude_social',
                'Sikap sosial',
                filled($attitudes->get('SOCIAL')?->description),
                'Deskripsi sikap sosial belum diisi.',
            ),
            $this->component(
                'attendance',
                'Absensi',
                $attendance !== null,
                'Rekap absensi belum diisi.',
            ),
        ];

        if ($expectedAssignments->isNotEmpty()) {
            $missingGrades = $expectedAssignments
                ->filter(function ($assignment) use ($gradesByAssignment): bool {
                    /** @var ReportGrade|null $grade */
                    $grade = $gradesByAssignment->get($assignment->getKey());

                    return $grade === null || $grade->score === null || blank($grade->description);
                })
                ->count();

            $components[] = $this->component(
                'grades',
                'Nilai dan deskripsi mata pelajaran',
                $missingGrades === 0,
                $missingGrades === 0
                    ? 'Semua nilai sudah lengkap.'
                    : sprintf('%d mata pelajaran belum lengkap.', $missingGrades),
            );
        }

        if ($reportCard->semester?->code?->value === 'GENAP'
            && $reportCard->semester?->status?->value !== 'CLOSED'
            && $reportCard->semester?->academicYear?->status?->value !== 'CLOSED') {
            $components[] = $this->component('academic_decision', 'Keputusan wali kelas', $this->decisions->forReport($reportCard) !== null,
                'Keputusan kenaikan atau kelulusan belum diisi oleh wali kelas.');
        }

        $missing = collect($components)
            ->where('required', true)
            ->where('complete', false)
            ->map(fn (array $component): array => [
                'key' => $component['key'],
                'label' => $component['label'],
                'message' => $component['message'],
            ])
            ->values()
            ->all();

        $completed = collect($components)->where('complete', true)->count();

        return [
            'complete' => $missing === [],
            'completed' => $completed,
            'total' => count($components),
            'missing' => $missing,
            'components' => $components,
        ];
    }

    public function assertComplete(ReportCard $reportCard): void
    {
        // The archive transition calls this gate so an incomplete card cannot
        // become a locked printable output.
        $summary = $this->summary($reportCard);

        if ($summary['complete']) {
            return;
        }

        throw ValidationException::withMessages([
            'completeness' => collect($summary['missing'])
                ->pluck('message')
                ->implode(' '),
        ]);
    }

    /**
     * @return array{key: string, label: string, required: bool, complete: bool, message: string}
     */
    private function component(string $key, string $label, bool $complete, string $message): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'required' => true,
            'complete' => $complete,
            'message' => $complete ? 'Lengkap.' : $message,
        ];
    }
}
