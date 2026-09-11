<?php

namespace Tests\Feature\Booking;

use App\Enums\Booking\CarType;
use App\Models\Booking\BookingService;
use App\Models\Booking\PriceRule;
use Database\Seeders\BookingCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPriceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_returns_unit_prices_for_catalog(): void
    {
        $this->seed(BookingCatalogSeeder::class);

        $mountingId = BookingService::where('name', 'Снятие и установка колёс')->value('id');
        $valveId = BookingService::where('name', 'Замена вентиля')->value('id');

        $response = $this->getJson('/api/booking/price?radius=13&car_type=passenger')->assertOk();

        $prices = $response->json('data.unit_prices');
        $this->assertCount(10, $prices);
        $this->assertSame(15000, $prices[$mountingId]); // прайс, лёгковые R12–15
        $this->assertSame(5000, $prices[$valveId]); // без правил — base_price
    }

    public function test_price_returns_422_when_combo_rule_missing(): void
    {
        $this->seed(BookingCatalogSeeder::class);

        // услуга с правилом только для R13: комбинация R20 — потеряна
        $service = BookingService::where('name', 'Замена вентиля')->firstOrFail();
        PriceRule::create([
            'service_id' => $service->id,
            'radius' => 13,
            'car_type' => CarType::Passenger,
            'price' => 5000,
        ]);

        $this->getJson('/api/booking/price?radius=20&car_type=passenger')
            ->assertUnprocessable();
    }
}
