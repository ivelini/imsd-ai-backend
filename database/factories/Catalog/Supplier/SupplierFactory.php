<?php

namespace Database\Factories\Catalog\Supplier;

use App\Models\Catalog\Supplier\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'code' => fake()->unique()->bothify('SUP-####'),
        ];
    }
}
