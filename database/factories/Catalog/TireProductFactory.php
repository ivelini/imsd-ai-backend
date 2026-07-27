<?php

namespace Database\Factories\Catalog;

use App\Models\Catalog\Brand;
use App\Models\Catalog\TireProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TireProduct> */
class TireProductFactory extends Factory
{
    protected $model = TireProduct::class;

    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'name' => fake()->unique()->bothify('Tire-??-###'),
            'ean' => fake()->unique()->ean8(),
            'season' => 'summer',
            'width' => fake()->randomElement([175, 185, 195, 205, 215, 225]),
            'profile' => fake()->randomElement([45, 50, 55, 60, 65, 70]),
            'diameter' => fake()->randomElement(['14', '15', '16', '17', '18']),
        ];
    }
}
