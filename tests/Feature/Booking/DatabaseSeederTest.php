<?php

namespace Tests\Feature\Booking;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingService;
use App\Models\Booking\ComplexService;
use App\Models\Booking\PriceRule;
use App\Models\Booking\ScheduleTemplate;
use App\Models\Booking\Slot;
use App\Models\System\Setting;
use App\Models\User;
use Database\Seeders\BookingCatalogSeeder;
use Database\Seeders\BookingScheduleSeeder;
use Database\Seeders\BookingSlotSeeder;
use Database\Seeders\DemoBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_price_cube_by_real_price_list(): void
    {
        $this->seed();

        $mounting = BookingService::where('name', 'Снятие и установка колёс')->firstOrFail();

        // 5 работ × R13–R21 (9) × 3 типа = 135 правил; цены прайса за 1 колесо (копейки)
        $this->assertSame(135, PriceRule::count());
        $this->assertSame(27, $mounting->priceRules()->count());
        $this->assertDatabaseHas('booking_price_rules', [
            'service_id' => $mounting->id,
            'radius' => 13,
            'car_type' => CarType::Passenger->value,
            'price' => 15000, // лёгковые, R12–R15
        ]);
        $this->assertDatabaseHas('booking_price_rules', [
            'service_id' => $mounting->id,
            'radius' => 21,
            'car_type' => CarType::Passenger->value,
            'price' => 38000, // лёгковые, R20–R21
        ]);
        $this->assertDatabaseHas('booking_price_rules', [
            'service_id' => $mounting->id,
            'radius' => 16,
            'car_type' => CarType::Crossover->value,
            'price' => 30000, // внедорожная колонка, R16–R17
        ]);
        $this->assertSame(0, PriceRule::where('car_type', CarType::Truck)->count());
    }

    public function test_seed_extra_works_without_rules(): void
    {
        $this->seed();

        $this->assertSame(10, BookingService::count());

        $valve = BookingService::where('name', 'Замена вентиля')->firstOrFail();
        $this->assertSame(5000, $valve->base_price->toKopecks());
        $this->assertSame(0, $valve->priceRules()->count());

        $utilization = BookingService::where('name', 'Утилизация шины')->firstOrFail();
        $this->assertSame(20000, $utilization->base_price->toKopecks());

        // комплектные строки прайса («при покупке», «4 колеса») в каталог не заводим
        $this->assertSame(0, BookingService::where('name', 'like', '%при покупке%')->count());
        $this->assertNull(BookingService::where('name', 'like', '%4 колеса%')->first());
    }

    public function test_seed_seasonal_complex_contains_four_works(): void
    {
        $this->seed();

        $complex = ComplexService::where('name', 'Сезонный шиномонтаж')->firstOrFail();
        $this->assertTrue($complex->is_active);
        $this->assertSame(
            ['Снятие и установка колёс', 'Демонтаж колёс', 'Монтаж колёс', 'Балансировка колёс'],
            $complex->services()->orderBy('booking_services.id')->pluck('name')->all(),
        );
    }

    public function test_seed_schedule_and_settings(): void
    {
        $this->seed();

        $this->assertSame(7, ScheduleTemplate::count());
        $this->assertGreaterThan(0, Setting::count());
    }

    public function test_seed_demo_slots_and_bookings_around_today(): void
    {
        $this->seed([BookingCatalogSeeder::class, BookingScheduleSeeder::class, BookingSlotSeeder::class, DemoBookingSeeder::class]);

        $this->assertGreaterThanOrEqual(100, Slot::count());
        $this->assertGreaterThanOrEqual(10, Booking::count());
        $this->assertGreaterThanOrEqual(10, User::count());
    }

    public function test_seed_bookings_carry_parameters_snapshot(): void
    {
        $this->seed([BookingCatalogSeeder::class, BookingScheduleSeeder::class, BookingSlotSeeder::class, DemoBookingSeeder::class]);

        $this->assertGreaterThan(0, Booking::whereNotNull('radius')->whereNotNull('car_type')->count());
        $this->assertGreaterThan(0, Booking::whereNotNull('plate')->count());
    }

    public function test_seed_bookings_have_history_and_future(): void
    {
        $this->seed([BookingCatalogSeeder::class, BookingScheduleSeeder::class, BookingSlotSeeder::class, DemoBookingSeeder::class]);

        $today = now()->toDateString();

        $this->assertGreaterThan(0, Booking::where('status', BookingStatus::Done)
            ->whereHas('slot', fn ($q) => $q->whereDate('date', '<', $today))
            ->count());

        $this->assertGreaterThan(0, Booking::where('status', BookingStatus::Confirmed)
            ->whereHas('slot', fn ($q) => $q->whereDate('date', '>=', $today))
            ->count());
    }
}
