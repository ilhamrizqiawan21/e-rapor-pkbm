<?php

namespace Tests\Unit\Services;

use App\Models\Institution;
use App\Models\PredicateRange;
use App\Services\PredicateRangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PredicateRangeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_default_ranges_and_converts_scores(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Predikat Uji']);
        $service = app(PredicateRangeService::class);

        $ranges = $service->ensureDefaults($institution);

        $this->assertCount(4, $ranges);
        $this->assertSame('A', $service->predicateFor($institution, 90)?->code);
        $this->assertSame('B', $service->predicateFor($institution, 80)?->code);
        $this->assertSame('C', $service->predicateFor($institution, 60)?->code);
        $this->assertSame('D', $service->predicateFor($institution, 20)?->code);
    }

    public function test_it_rejects_out_of_range_and_overlapping_ranges(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Overlap Uji']);
        $service = app(PredicateRangeService::class);
        $service->ensureDefaults($institution);

        $this->expectException(ValidationException::class);
        $service->create($institution, [
            'code' => 'X', 'label' => 'Overlap', 'minimum_score' => 50, 'maximum_score' => 60, 'display_order' => 5, 'active' => true,
        ]);
    }

    public function test_it_allows_adjacent_ranges_and_snapshots_only_active_rules(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Snapshot Uji']);
        $service = app(PredicateRangeService::class);
        PredicateRange::query()->create([
            'institution_id' => $institution->id, 'code' => 'L', 'label' => 'Lulus', 'minimum_score' => 0, 'maximum_score' => 49, 'display_order' => 1, 'active' => true,
        ]);
        PredicateRange::query()->create([
            'institution_id' => $institution->id, 'code' => 'T', 'label' => 'Tinggi', 'minimum_score' => 50, 'maximum_score' => 100, 'display_order' => 2, 'active' => false,
        ]);

        $this->assertSame('L', $service->predicateFor($institution, 49)?->code);
        $this->assertNull($service->predicateFor($institution, 80));
        $this->assertSame([['code' => 'L', 'label' => 'Lulus', 'minimum_score' => 0, 'maximum_score' => 49]], $service->snapshotRules($institution));
    }
}
