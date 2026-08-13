<?php

namespace Tests\Feature\Services\Catalog;

use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Services\Catalog\PriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** findRule — поиск правила наценки склада (ходит в БД). */
class PriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_rule_returns_array_with_smallest_price_from(): void
    {
        $warehouse = Warehouse::factory()->create();
        WarehouseMarkupRule::create([
            'warehouse_id' => $warehouse->id, 'price_from' => 100, 'price_to' => 500, 'coefficient' => 1.2,
        ]);
        WarehouseMarkupRule::create([
            'warehouse_id' => $warehouse->id, 'price_from' => 50, 'price_to' => 600, 'coefficient' => 1.5,
        ]);
        $other = Warehouse::factory()->create();
        WarehouseMarkupRule::create([
            'warehouse_id' => $other->id, 'price_from' => 0, 'price_to' => 500, 'coefficient' => 2.0,
        ]);

        $rule = app(PriceCalculator::class)->findRule(200.0, $warehouse->id);

        $this->assertIsArray($rule);
        $this->assertSame(1.5, $rule['coefficient']);
    }

    public function test_find_rule_returns_null_when_no_covering_rule(): void
    {
        $warehouse = Warehouse::factory()->create();
        WarehouseMarkupRule::create([
            'warehouse_id' => $warehouse->id, 'price_from' => 300, 'price_to' => 500, 'coefficient' => 1.5,
        ]);

        $this->assertNull(app(PriceCalculator::class)->findRule(100.0, $warehouse->id));
    }
}
