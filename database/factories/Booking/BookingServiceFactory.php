<?php

namespace Database\Factories\Booking;

use App\Enums\Booking\ServiceCategory;
use App\Models\Booking\BookingService;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BookingService> */
class BookingServiceFactory extends Factory
{
    protected $model = BookingService::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'category' => fake()->randomElement(ServiceCategory::cases()),
            'is_active' => true,
            'base_price' => fake()->numberBetween(300, 3000) * 100, // копейки
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
