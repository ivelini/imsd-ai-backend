<?php

namespace Tests\Feature\Booking;

use App\Enums\Booking\CarType;
use App\Models\Booking\BookingService;
use App\Models\Booking\PriceRule;
use App\Services\Booking\PriceCalculator;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function calculator(): PriceCalculator
    {
        return app(PriceCalculator::class);
    }

    private function service(string $name, int $basePrice): BookingService
    {
        return BookingService::create(['name' => $name, 'category' => 'tire', 'is_active' => true, 'base_price' => $basePrice]);
    }

    private function rule(BookingService $service, int $radius, CarType $carType, int $price): void
    {
        PriceRule::create([
            'service_id' => $service->id,
            'radius' => $radius,
            'car_type' => $carType,
            'price' => $price,
        ]);
    }

    public function test_unit_price_multiplied_by_quantity(): void
    {
        $mounting = $this->service('Снятие и установка колёс', 15000);
        $this->rule($mounting, 13, CarType::Passenger, 15000);

        $quote = $this->calculator()->calculate(collect([$mounting]), 13, CarType::Passenger, [$mounting->id => 4]);

        $line = $quote->lines[0];
        $this->assertSame(15000, $line->unitPrice->toKopecks());
        $this->assertSame(4, $line->quantity);
        $this->assertSame(60000, $line->price->toKopecks());
        $this->assertSame(60000, $quote->total->toKopecks());
    }

    public function test_rule_by_radius_and_type(): void
    {
        $mounting = $this->service('Снятие и установка колёс', 15000);
        $this->rule($mounting, 13, CarType::Passenger, 15000);
        $this->rule($mounting, 13, CarType::Crossover, 22000);

        $quote = $this->calculator()->calculate(collect([$mounting]), 13, CarType::Crossover, [$mounting->id => 1]);

        $this->assertSame(22000, $quote->lines[0]->price->toKopecks());
    }

    public function test_service_without_rules_uses_base_price(): void
    {
        $valve = $this->service('Замена вентиля', 5000);

        $quote = $this->calculator()->calculate(collect([$valve]), 16, CarType::Passenger, [$valve->id => 2]);

        $line = $quote->lines[0];
        $this->assertSame(5000, $line->unitPrice->toKopecks());
        $this->assertSame(2, $line->quantity);
        $this->assertSame(10000, $line->price->toKopecks());
        $this->assertSame(10000, $quote->total->toKopecks());
    }

    public function test_throw_when_combo_rule_missing(): void
    {
        $mounting = $this->service('Снятие и установка колёс', 15000);
        $this->rule($mounting, 13, CarType::Passenger, 15000);

        $this->expectException(DomainException::class);
        $this->expectExceptionCode(422);

        $this->calculator()->calculate(collect([$mounting]), 20, CarType::Passenger, [$mounting->id => 4]);
    }

    public function test_sums_services_with_quantities(): void
    {
        $mounting = $this->service('Снятие и установка колёс', 15000);
        $this->rule($mounting, 13, CarType::Passenger, 15000);
        $balancing = $this->service('Балансировка колёс', 14000);
        $this->rule($balancing, 13, CarType::Passenger, 14000);

        $quote = $this->calculator()->calculate(
            collect([$mounting, $balancing]),
            13,
            CarType::Passenger,
            [$mounting->id => 4, $balancing->id => 2],
        );

        $this->assertCount(2, $quote->lines);
        $this->assertSame([4, 2], array_column($quote->lines, 'quantity'));
        $this->assertSame(
            [60000, 28000],
            collect($quote->lines)->map(fn ($line) => $line->price->toKopecks())->all(),
        );
        $this->assertSame(88000, $quote->total->toKopecks());
    }
}
