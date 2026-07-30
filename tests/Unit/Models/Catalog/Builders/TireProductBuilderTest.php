<?php

namespace Tests\Unit\Models\Catalog\Builders;

use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Тесты методов фильтрации TireProductBuilder. */
class TireProductBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        TireProduct::factory()->createMany([
            ['width' => 205, 'profile' => 55, 'diameter' => 'R16', 'load_index' => '91', 'speed_index' => 'H', 'year' => 2023, 'is_bestseller' => true, 'is_new' => false, 'is_studded' => true, 'is_runflat' => false, 'is_xl' => false, 'season' => 'winter'],
            ['width' => 225, 'profile' => 55, 'diameter' => 'R17', 'load_index' => '94', 'speed_index' => 'V', 'year' => 2024, 'is_bestseller' => false, 'is_new' => true, 'is_studded' => false, 'is_runflat' => true, 'is_xl' => true, 'season' => 'summer'],
            ['width' => 245, 'profile' => 45, 'diameter' => 'R18', 'load_index' => '98', 'speed_index' => 'W', 'year' => 2025, 'is_bestseller' => true, 'is_new' => true, 'is_studded' => false, 'is_runflat' => false, 'is_xl' => false, 'season' => 'all-season'],
        ]);
    }

    public function test_by_widths_filters_by_multiple_widths(): void
    {
        $result = TireProduct::query()->byWidths([205, 225])->get();

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing([205, 225], $result->pluck('width')->all());
    }

    public function test_by_widths_returns_empty_for_no_match(): void
    {
        $result = TireProduct::query()->byWidths([999])->get();

        $this->assertCount(0, $result);
    }

    public function test_by_profiles_filters_by_multiple_profiles(): void
    {
        $result = TireProduct::query()->byProfiles([55])->get();

        $this->assertCount(2, $result);
        $this->assertEquals([55, 55], $result->pluck('profile')->all());
    }

    public function test_by_diameters_filters_by_multiple_diameters(): void
    {
        $result = TireProduct::query()->byDiameters(['R16', 'R17'])->get();

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing(['R16', 'R17'], $result->pluck('diameter')->all());
    }

    public function test_by_load_indexes_filters_by_multiple_values(): void
    {
        $result = TireProduct::query()->byLoadIndexes(['91', '98'])->get();

        $this->assertCount(2, $result);
    }

    public function test_by_speed_indexes_filters_by_multiple_values(): void
    {
        $result = TireProduct::query()->bySpeedIndexes(['H', 'W'])->get();

        $this->assertCount(2, $result);
    }

    public function test_by_years_filters_by_multiple_years(): void
    {
        $result = TireProduct::query()->byYears([2023, 2025])->get();

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing([2023, 2025], $result->pluck('year')->all());
    }

    public function test_bestseller_filters_correctly(): void
    {
        $result = TireProduct::query()->bestseller(true)->get();

        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn ($t) => $t->is_bestseller));
    }

    public function test_bestseller_false_filters_non_bestsellers(): void
    {
        $result = TireProduct::query()->bestseller(false)->get();

        $this->assertCount(1, $result);
        $this->assertFalse($result->first()->is_bestseller);
    }

    public function test_is_new_filters_correctly(): void
    {
        $result = TireProduct::query()->isNew(true)->get();

        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn ($t) => $t->is_new));
    }

    public function test_filters_can_be_combined(): void
    {
        $result = TireProduct::query()
            ->byWidths([225, 245])
            ->byProfiles([45, 55])
            ->byDiameters(['R17', 'R18'])
            ->get();

        $this->assertCount(2, $result);
    }

    public function test_chained_filters_with_no_overlap_return_empty(): void
    {
        $result = TireProduct::query()
            ->byWidths([205])
            ->byDiameters(['R18'])
            ->get();

        $this->assertCount(0, $result);
    }
}
