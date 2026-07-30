<?php

namespace Tests\Unit\Actions\Catalog;

use App\Actions\Catalog\Tire\GetTireDimensions;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Тесты Action GetTireDimensions — доступные значения фильтров шин. */
class GetTireDimensionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        TireProduct::factory()->createMany([
            ['width' => 205, 'profile' => 55, 'diameter' => 'R16', 'load_index' => '91', 'speed_index' => 'H', 'year' => 2023, 'is_studded' => true, 'is_runflat' => false, 'is_xl' => false, 'is_bestseller' => true, 'is_new' => false, 'season' => 'winter'],
            ['width' => 225, 'profile' => 55, 'diameter' => 'R17', 'load_index' => '94', 'speed_index' => 'V', 'year' => 2024, 'is_studded' => false, 'is_runflat' => true, 'is_xl' => true, 'is_bestseller' => false, 'is_new' => true, 'season' => 'summer'],
            ['width' => 245, 'profile' => 45, 'diameter' => 'R18', 'load_index' => null, 'speed_index' => null, 'year' => 2025, 'is_studded' => false, 'is_runflat' => false, 'is_xl' => false, 'is_bestseller' => true, 'is_new' => true, 'season' => 'all-season'],
        ]);
    }

    public function test_execute_returns_all_distinct_widths(): void
    {
        $action = new GetTireDimensions;
        $result = $action->execute([]);

        $this->assertEqualsCanonicalizing([205, 225, 245], $result['widths']);
    }

    public function test_execute_returns_all_distinct_diameters(): void
    {
        $action = new GetTireDimensions;
        $result = $action->execute([]);

        $this->assertEqualsCanonicalizing(['R16', 'R17', 'R18'], $result['diameters']);
    }

    public function test_execute_returns_all_seasons_statically(): void
    {
        $action = new GetTireDimensions;
        $result = $action->execute([]);

        $seasonValues = array_column($result['seasons'], 'value');
        $this->assertContains('winter', $seasonValues);
        $this->assertContains('summer', $seasonValues);
        $this->assertContains('all-season', $seasonValues);
    }

    public function test_execute_respects_width_filter_for_profiles(): void
    {
        $action = new GetTireDimensions;
        $result = $action->execute(['width' => ['205', '225']]);

        // Profile 45 only exists for width 245, should be absent
        $this->assertEqualsCanonicalizing([55], $result['profiles']);
    }

    public function test_execute_respects_season_filter(): void
    {
        $action = new GetTireDimensions;
        $result = $action->execute(['season' => 'winter']);

        // Only one product has season=winter (width=205)
        $this->assertEqualsCanonicalizing([205], $result['widths']);
    }

    public function test_execute_returns_bool_values_from_data(): void
    {
        $action = new GetTireDimensions;
        $result = $action->execute([]);

        // All 3 products have is_studded = true/false
        $this->assertContains(true, $result['is_studded']);
        $this->assertContains(false, $result['is_studded']);
    }

    public function test_execute_returns_single_bool_value_when_all_same(): void
    {
        // Create products all with is_studded=true
        TireProduct::query()->delete();
        TireProduct::factory()->createMany([
            ['is_studded' => true, 'width' => 205, 'profile' => 55, 'diameter' => 'R16'],
            ['is_studded' => true, 'width' => 215, 'profile' => 60, 'diameter' => 'R17'],
        ]);

        $action = new GetTireDimensions;
        $result = $action->execute([]);

        $this->assertEquals([true], $result['is_studded']);
    }

    public function test_execute_excludes_null_dimensions(): void
    {
        $action = new GetTireDimensions;
        $result = $action->execute([]);

        // Third product has null load_index and speed_index
        $this->assertEqualsCanonicalizing(['91', '94'], $result['load_indexes']);
        $this->assertEqualsCanonicalizing(['H', 'V'], $result['speed_indexes']);
    }

    public function test_execute_returns_brands_and_models_structure(): void
    {
        $action = new GetTireDimensions;
        $result = $action->execute([]);

        $this->assertIsArray($result['brands']);
        $this->assertIsArray($result['models']);

        if (! empty($result['brands'])) {
            $brand = $result['brands'][0];
            $this->assertArrayHasKey('value', $brand);
            $this->assertArrayHasKey('label', $brand);
        }
    }
}
