<?php

namespace Database\Factories\Catalog\Model;

use App\Models\Catalog\Model\ProductModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductModel> */
class ProductModelFactory extends Factory
{
    protected $model = ProductModel::class;

    public function definition(): array
    {
        return [
            'brand_id' => 1,
            'name' => fake()->word(),
            'slug' => fake()->slug(),
            'type' => 'tire',
        ];
    }
}
