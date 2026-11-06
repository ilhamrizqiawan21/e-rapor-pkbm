<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\PredicateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PredicateRangeService
{
    /**
     * @return array<int, array{code: string, label: string, minimum_score: int, maximum_score: int, display_order: int}>
     */
    public function defaultDefinitions(): array
    {
        return [
            ['code' => 'A', 'label' => 'Sangat Baik', 'minimum_score' => 86, 'maximum_score' => 100, 'display_order' => 1],
            ['code' => 'B', 'label' => 'Baik', 'minimum_score' => 71, 'maximum_score' => 85, 'display_order' => 2],
            ['code' => 'C', 'label' => 'Cukup', 'minimum_score' => 56, 'maximum_score' => 70, 'display_order' => 3],
            ['code' => 'D', 'label' => 'Perlu Bimbingan', 'minimum_score' => 0, 'maximum_score' => 55, 'display_order' => 4],
        ];
    }

    public function ensureDefaults(Institution $institution): Collection
    {
        return DB::transaction(function () use ($institution): Collection {
            if (! PredicateRange::query()->where('institution_id', $institution->getKey())->exists()) {
                foreach ($this->defaultDefinitions() as $definition) {
                    PredicateRange::query()->create(['institution_id' => $institution->getKey(), ...$definition, 'active' => true]);
                }
            }

            return $this->query($institution)->get();
        });
    }

    public function query(Institution $institution, bool $activeOnly = false): Builder
    {
        return PredicateRange::query()
            ->where('institution_id', $institution->getKey())
            ->when($activeOnly, fn ($query) => $query->where('active', true))
            ->orderBy('display_order')
            ->orderBy('minimum_score', 'desc')
            ->orderBy('id');
    }

    public function validateScores(int|float|string $minimumScore, int|float|string $maximumScore): void
    {
        $minimumScore = (int) $minimumScore;
        $maximumScore = (int) $maximumScore;

        if ($minimumScore < 0 || $minimumScore > 100 || $maximumScore < 0 || $maximumScore > 100) {
            throw ValidationException::withMessages([
                'minimum_score' => 'Rentang predikat harus berada pada nilai 0 sampai 100.',
                'maximum_score' => 'Rentang predikat harus berada pada nilai 0 sampai 100.',
            ]);
        }

        if ($minimumScore > $maximumScore) {
            throw ValidationException::withMessages([
                'minimum_score' => 'Nilai minimum tidak boleh lebih besar dari nilai maksimum.',
            ]);
        }
    }

    public function assertNoOverlap(Institution $institution, int $minimumScore, int $maximumScore, ?PredicateRange $ignore = null): void
    {
        $this->validateScores($minimumScore, $maximumScore);

        $overlap = $this->query($institution)
            ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->getKey()))
            ->get()
            ->first(fn (PredicateRange $range): bool => $minimumScore <= $range->maximum_score && $maximumScore >= $range->minimum_score);

        if ($overlap !== null) {
            throw ValidationException::withMessages([
                'minimum_score' => "Rentang bertumpang tindih dengan predikat {$overlap->code}.",
                'maximum_score' => "Rentang bertumpang tindih dengan predikat {$overlap->code}.",
            ]);
        }
    }

    public function create(Institution $institution, array $attributes): PredicateRange
    {
        $this->assertNoOverlap($institution, (int) $attributes['minimum_score'], (int) $attributes['maximum_score']);

        return PredicateRange::query()->create(['institution_id' => $institution->getKey(), ...$attributes]);
    }

    public function update(PredicateRange $range, array $attributes): PredicateRange
    {
        $institution = $range->institution;
        $this->assertNoOverlap($institution, (int) $attributes['minimum_score'], (int) $attributes['maximum_score'], $range);
        $range->update($attributes);

        return $range->refresh();
    }

    public function predicateFor(Institution $institution, int $score): ?PredicateRange
    {
        if ($score < 0 || $score > 100) {
            throw ValidationException::withMessages(['score' => 'Nilai harus berada pada rentang 0 sampai 100.']);
        }

        return $this->query($institution, true)
            ->get()
            ->first(fn (PredicateRange $range): bool => $range->contains($score));
    }

    /**
     * @return array<int, array{code: string, label: string, minimum_score: int, maximum_score: int}>
     */
    public function snapshotRules(Institution $institution): array
    {
        return $this->query($institution, true)
            ->get()
            ->map(fn (PredicateRange $range): array => $range->toSnapshot())
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{code: string, label: string, minimum_score: int, maximum_score: int}>  $rules
     * @return array{code: string, label: string, minimum_score: int, maximum_score: int}|null
     */
    public function predicateFromSnapshot(array $rules, int $score): ?array
    {
        if ($score < 0 || $score > 100) {
            throw ValidationException::withMessages(['score' => 'Nilai harus berada pada rentang 0 sampai 100.']);
        }

        return collect($rules)->first(fn (array $range): bool => $score >= $range['minimum_score'] && $score <= $range['maximum_score']);
    }
}
