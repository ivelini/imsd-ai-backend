<?php

namespace Database\Factories\Catalog;

use App\Models\Catalog\Brand;
use App\Models\Catalog\WheelProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WheelProduct> */
class WheelProductFactory extends Factory
{
    protected $model = WheelProduct::class;

    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'name' => fake()->unique()->bothify('Wheel-??-###'),
            'ean' => fake()->unique()->ean8(),
            'type' => 'alloy',
            'diameter' => fake()->randomElement([15, 16, 17, 18]),
            'width' => fake()->randomFloat(1, 5, 9),
        ];
    }
}
