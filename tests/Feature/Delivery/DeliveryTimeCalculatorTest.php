<?php

namespace Tests\Feature\Delivery;

use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\DeliverySchedule;
use App\Services\Delivery\DeliveryTimeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesCity;
use Tests\TestCase;

/** Расчёт срока доставки со склада до города. */
class DeliveryTimeCalculatorTest extends TestCase
{
    use CreatesCity, RefreshDatabase;

    private DeliveryTimeCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new DeliveryTimeCalculator;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_calculate_returns_null_when_no_schedules(): void
    {
        $warehouse = Warehouse::factory()->create();
        $city = $this->createCity();

        $result = $this->calculator->calculate($warehouse->id, $city->id);

        $this->assertNull($result);
    }

    public function test_calculate_before_cutoff_today(): void
    {
        Carbon::setTestNow(now()->startOfDay()->addHours(10));
        $warehouse = Warehouse::factory()->create();
        $city = $this->createCity();

        $this->createScheduleForToday($warehouse);
        CityDeliveryTime::create(['city_id' => $city->id, 'delivery_days' => 1, 'priority' => 1]);

        $result = $this->calculator->calculate($warehouse->id, $city->id);

        $this->assertSame(3, $result);
    }

    public function test_calculate_after_cutoff_today(): void
    {
        Carbon::setTestNow(now()->startOfDay()->addHours(20));
        $warehouse = Warehouse::factory()->create();
        $city = $this->createCity();

        $this->createScheduleForToday($warehouse);
        CityDeliveryTime::create(['city_id' => $city->id, 'delivery_days' => 1, 'priority' => 1]);

        $result = $this->calculator->calculate($warehouse->id, $city->id);

        $this->assertSame(6, $result);
    }

    public function test_calculate_returns_null_when_no_city_delivery_time(): void
    {
        $warehouse = Warehouse::factory()->create();
        $city = $this->createCity();

        $this->createScheduleForToday($warehouse);

        $result = $this->calculator->calculate($warehouse->id, $city->id);

        $this->assertNull($result);
    }

    public function test_delivery_range_returns_weekly_envelope(): void
    {
        $schedules = collect([
            DeliverySchedule::make([
                'warehouse_id' => 1, 'day_of_week' => 0, 'cutoff_time' => '14:00', 'days_before' => 1, 'days_after' => 2,
            ]),
            DeliverySchedule::make([
                'warehouse_id' => 1, 'day_of_week' => 1, 'cutoff_time' => '14:00', 'days_before' => 1, 'days_after' => 2,
            ]),
            DeliverySchedule::make([
                'warehouse_id' => 1, 'day_of_week' => 5, 'cutoff_time' => '12:00', 'days_before' => 2, 'days_after' => 3,
            ]),
        ]);

        $this->assertSame(['min' => 1, 'max' => 3], DeliveryTimeCalculator::deliveryRange($schedules));
    }

    public function test_delivery_range_null_without_schedules(): void
    {
        $this->assertNull(DeliveryTimeCalculator::deliveryRange(collect()));
    }

    public function test_delivery_range_single_day(): void
    {
        $schedules = collect([
            DeliverySchedule::make([
                'warehouse_id' => 1, 'day_of_week' => 2, 'cutoff_time' => '14:00', 'days_before' => 2, 'days_after' => 3,
            ]),
        ]);

        $this->assertSame(['min' => 2, 'max' => 3], DeliveryTimeCalculator::deliveryRange($schedules));
    }
}
