<?php

namespace App\Exceptions;

use App\Models\ReportGrade;
use Carbon\CarbonImmutable;
use RuntimeException;

final class GradeConcurrencyConflict extends RuntimeException
{
    public function __construct(
        private readonly ReportGrade $grade,
        private readonly CarbonImmutable $expectedUpdatedAt,
        private readonly CarbonImmutable $lastUpdatedAt,
    ) {
        parent::__construct(
            'Nilai berubah setelah halaman dibuka. Muat ulang data sebelum menyimpan kembali.',
        );
    }

    public function grade(): ReportGrade
    {
        return $this->grade;
    }

    public function expectedUpdatedAt(): CarbonImmutable
    {
        return $this->expectedUpdatedAt;
    }

    public function lastUpdatedAt(): CarbonImmutable
    {
        return $this->lastUpdatedAt;
    }

    /**
     * Stable payload for an HTTP or Livewire conflict response.
     *
     * The latest values are intentionally included so the caller can show a
     * warning without issuing another query or accidentally overwriting them.
     */
    public function payload(): array
    {
        return [
            'code' => 'GRADE_CONCURRENCY_CONFLICT',
            'message' => $this->getMessage(),
            'grade_id' => $this->grade->getKey(),
            'expected_updated_at' => $this->expectedUpdatedAt->toIso8601String(),
            'last_updated_at' => $this->lastUpdatedAt->toIso8601String(),
            'last_score' => $this->grade->score,
            'last_predicate' => $this->grade->predicate,
            'last_description' => $this->grade->description,
            'last_subject_assignment_id' => $this->grade->subject_assignment_id,
        ];
    }
}
