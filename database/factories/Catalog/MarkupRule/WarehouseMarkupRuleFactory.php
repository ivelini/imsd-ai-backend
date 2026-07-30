<?php

namespace Database\Factories\Catalog\MarkupRule;

use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Catalog\Warehouse\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WarehouseMarkupRule> */
class WarehouseMarkupRuleFactory extends Factory
{
    protected $model = WarehouseMarkupRule::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'price_from' => fake()->randomFloat(2, 0, 5000),
            'price_to' => fake()->randomFloat(2, 5001, 50000),
            'coefficient' => fake()->randomFloat(2, 1.0, 2.5),
        ];
    }
}
