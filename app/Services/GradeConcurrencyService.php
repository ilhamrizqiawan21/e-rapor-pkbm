<?php

namespace App\Services;

use App\Exceptions\GradeConcurrencyConflict;
use App\Models\ReportGrade;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class GradeConcurrencyService
{
    /**
     * Fields that a grade-entry surface may edit. Identity and subject
     * snapshots are deliberately excluded so a stale form cannot move a
     * value onto another teacher's subject assignment.
     *
     * @var list<string>
     */
    private const MUTABLE_ATTRIBUTES = [
        'score',
        'predicate',
        'description',
        'description_snippet_id',
        'status',
    ];

    public function assertFresh(
        ReportGrade $grade,
        DateTimeInterface|string $expectedUpdatedAt,
    ): ReportGrade {
        $current = $this->currentGrade($grade);
        $expected = $this->parseTimestamp($expectedUpdatedAt);

        $this->assertTimestampMatches($current, $expected);

        return $current;
    }

    /**
     * Atomically update an existing grade only when its page snapshot is
     * still current.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(
        ReportGrade $grade,
        DateTimeInterface|string $expectedUpdatedAt,
        array $attributes,
    ): ReportGrade {
        $this->assertMutableAttributes($attributes);
        $expected = $this->parseTimestamp($expectedUpdatedAt);

        return DB::transaction(function () use ($grade, $expected, $attributes): ReportGrade {
            $current = ReportGrade::query()
                ->whereKey($grade->getKey())
                ->lockForUpdate()
                ->first();

            if (! $current instanceof ReportGrade) {
                throw (new ModelNotFoundException)->setModel(ReportGrade::class, [$grade->getKey()]);
            }

            $this->assertTimestampMatches($current, $expected);

            $current->fill($attributes);
            $current->saveOrFail();

            return $current->refresh();
        });
    }

    /**
     * Convenience method for the most common grade-entry operation.
     */
    public function updateScore(
        ReportGrade $grade,
        DateTimeInterface|string $expectedUpdatedAt,
        ?int $score,
    ): ReportGrade {
        $predicate = null;

        foreach ($grade->predicate_ranges_snapshot ?? [] as $range) {
            if ($score !== null && $score >= (int) ($range['minimum_score'] ?? 0) && $score <= (int) ($range['maximum_score'] ?? -1)) {
                $predicate = $range['code'] ?? null;
                break;
            }
        }

        return $this->update($grade, $expectedUpdatedAt, [
            'score' => $score,
            'predicate' => $predicate,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertMutableAttributes(array $attributes): void
    {
        if ($attributes === []) {
            throw new InvalidArgumentException('Setidaknya satu field nilai harus diperbarui.');
        }

        $unsupported = array_values(array_diff(array_keys($attributes), self::MUTABLE_ATTRIBUTES));

        if ($unsupported !== []) {
            throw new InvalidArgumentException(sprintf(
                'Field nilai tidak dapat diperbarui melalui concurrency service: %s.',
                implode(', ', $unsupported),
            ));
        }
    }

    private function currentGrade(ReportGrade $grade): ReportGrade
    {
        $current = ReportGrade::query()->find($grade->getKey());

        if (! $current instanceof ReportGrade) {
            throw (new ModelNotFoundException)->setModel(ReportGrade::class, [$grade->getKey()]);
        }

        return $current;
    }

    private function assertTimestampMatches(ReportGrade $grade, CarbonImmutable $expected): void
    {
        $lastUpdatedAt = $this->parseTimestamp($grade->updated_at);

        if (! $lastUpdatedAt->equalTo($expected)) {
            throw new GradeConcurrencyConflict($grade, $expected, $lastUpdatedAt);
        }
    }

    private function parseTimestamp(DateTimeInterface|string $timestamp): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($timestamp);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Timestamp updated_at nilai tidak valid.', 0, $exception);
        }
    }
}
