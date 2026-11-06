<?php

namespace App\Services;

use App\Models\ReportGrade;
use App\Models\SubjectAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class KkmAssignmentService
{
    public function assertValid(int|float|string $minimumScore): int
    {
        if (filter_var($minimumScore, FILTER_VALIDATE_INT) === false || (int) $minimumScore < 0 || (int) $minimumScore > 100) {
            throw ValidationException::withMessages(['minimum_score' => 'KKM harus berupa bilangan bulat dari 0 sampai 100.']);
        }

        return (int) $minimumScore;
    }

    public function assign(SubjectAssignment $assignment, int|float|string $minimumScore): SubjectAssignment
    {
        $minimumScore = $this->assertValid($minimumScore);

        if ($this->hasLockedGrades($assignment)) {
            throw new LogicException('KKM tidak dapat diubah karena sudah digunakan pada rapor terkunci.');
        }

        return DB::transaction(function () use ($assignment, $minimumScore): SubjectAssignment {
            $assignment->update(['minimum_score' => $minimumScore]);

            return $assignment->refresh();
        });
    }

    public function assertScore(?int $score): ?int
    {
        if ($score === null) {
            return null;
        }

        if ($score < 0 || $score > 100) {
            throw ValidationException::withMessages(['score' => 'Nilai harus berada pada rentang 0 sampai 100.']);
        }

        return $score;
    }

    public function hasLockedGrades(SubjectAssignment $assignment): bool
    {
        return ReportGrade::query()
            ->where('subject_assignment_id', $assignment->getKey())
            ->whereHas('reportCard', fn ($query) => $query->where('status', 'LOCKED'))
            ->exists();
    }
}
