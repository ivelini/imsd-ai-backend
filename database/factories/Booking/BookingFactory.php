<?php

namespace Database\Factories\Booking;

use App\Enums\Booking\BookingSource;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Models\Booking\Booking;
use App\Models\Booking\Slot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Booking> */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /** Буквы госномера, допустимые ГОСТом (кириллица: важно — faker'ов regexify режет многобайтные
     *  наборы по байтам и выдаёт невалидный UTF-8, на котором падает сериализация состояния Livewire). */
    private const PLATE_LETTERS = ['А', 'В', 'Е', 'К', 'М', 'Н', 'О', 'Р', 'С', 'Т', 'У', 'Х'];

    public function definition(): array
    {
        // слот из существующих строк сетки, иначе создаётся по требованию (как запись из админки)
        $slot = Slot::query()
            ->where('date', '>=', now()->toDateString())
            ->where('is_closed', false)
            ->inRandomOrder()
            ->first()
            ?? Slot::create(['date' => now()->addDays(rand(1, 20))->toDateString(), 'hour' => rand(9, 18)]);

        return [
            'user_id' => User::factory()->bookingClient(),
            'slot_id' => $slot->id,
            'start_time' => sprintf('%02d:00:00', $slot->hour),
            'status' => BookingStatus::Confirmed,
            'source' => BookingSource::Site,
            'radius' => fake()->numberBetween(13, 21),
            'car_type' => fake()->randomElement(CarType::bookable()),
            'plate' => fake()->boolean(70) ? self::plate() : null,
            'total_price' => 60000,
        ];
    }

    /** Госномер вида «А123ВС74» — собирается по частям: многобайтные буквы берутся по одной. */
    private static function plate(): string
    {
        return sprintf(
            '%s%03d%s%s%02d',
            fake()->randomElement(self::PLATE_LETTERS),
            fake()->numberBetween(1, 999),
            fake()->randomElement(self::PLATE_LETTERS),
            fake()->randomElement(self::PLATE_LETTERS),
            fake()->numberBetween(1, 99),
        );
    }

    public function forSlot(Slot $slot): static
    {
        return $this->state(fn () => ['slot_id' => $slot->id, 'start_time' => sprintf('%02d:00:00', $slot->hour)]);
    }

    public function done(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::Done]);
    }

    public function cancelled(string $reason = 'клиент передумал'): static
    {
        return $this->state(fn () => ['status' => BookingStatus::Cancelled, 'cancel_reason' => $reason]);
    }
}
