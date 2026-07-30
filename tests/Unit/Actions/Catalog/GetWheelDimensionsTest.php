<?php

namespace Tests\Unit\Actions\Catalog;

use App\Actions\Catalog\Wheel\GetWheelDimensions;
use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Тесты Action GetWheelDimensions — доступные значения фильтров дисков. */
class GetWheelDimensionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        WheelProduct::factory()->createMany([
            ['width' => 6.5, 'diameter' => 16, 'pcd' => '5x112', 'et' => 35.0, 'hub_diameter' => 57.1, 'type' => 'alloy', 'color' => 'black', 'is_bestseller' => true, 'is_new' => false],
            ['width' => 7.0, 'diameter' => 17, 'pcd' => '5x114.3', 'et' => 40.0, 'hub_diameter' => 66.6, 'type' => 'steel', 'color' => 'silver', 'is_bestseller' => false, 'is_new' => true],
            ['width' => 7.5, 'diameter' => 18, 'pcd' => null, 'et' => null, 'hub_diameter' => 72.6, 'type' => 'forged', 'color' => 'black', 'is_bestseller' => true, 'is_new' => true],
        ]);
    }

    public function test_execute_returns_all_distinct_widths(): void
    {
        $action = new GetWheelDimensions;
        $result = $action->execute([]);

        $this->assertEqualsCanonicalizing([6.5, 7.0, 7.5], $result['widths']);
    }

    public function test_execute_returns_all_wheel_types_statically(): void
    {
        $action = new GetWheelDimensions;
        $result = $action->execute([]);

        $typeValues = array_column($result['types'], 'value');
        $this->assertContains('alloy', $typeValues);
        $this->assertContains('steel', $typeValues);
        $this->assertContains('forged', $typeValues);
    }

    public function test_execute_respects_type_filter(): void
    {
        $action = new GetWheelDimensions;
        $result = $action->execute(['type' => 'alloy']);

        // Only alloy has width 6.5
        $this->assertEqualsCanonicalizing([6.5], $result['widths']);
    }

    public function test_execute_respects_width_filter(): void
    {
        $action = new GetWheelDimensions;
        $result = $action->execute(['width' => ['7.0', '7.5']]);

        // diameter 16 only exists for width 6.5
        $this->assertEqualsCanonicalizing([17, 18], $result['diameters']);
    }

    public function test_execute_excludes_null_pcd(): void
    {
        $action = new GetWheelDimensions;
        $result = $action->execute([]);

        $this->assertEqualsCanonicalizing(['5x112', '5x114.3'], $result['pcds']);
    }

    public function test_execute_excludes_null_et(): void
    {
        $action = new GetWheelDimensions;
        $result = $action->execute([]);

        $this->assertEqualsCanonicalizing([35.0, 40.0], $result['ets']);
    }

    public function test_execute_returns_distinct_colors(): void
    {
        $action = new GetWheelDimensions;
        $result = $action->execute([]);

        $this->assertEqualsCanonicalizing(['black', 'silver'], $result['colors']);
    }

    public function test_execute_returns_bool_values_from_data(): void
    {
        $action = new GetWheelDimensions;
        $result = $action->execute([]);

        $this->assertContains(true, $result['is_bestseller']);
        $this->assertContains(false, $result['is_bestseller']);
    }
}
