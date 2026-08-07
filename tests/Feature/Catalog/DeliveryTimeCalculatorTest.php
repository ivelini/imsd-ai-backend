<?php

namespace Tests\Feature\Catalog;

use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\City;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\DeliverySchedule;
use App\Models\Delivery\Region;
use App\Services\Catalog\DeliveryTimeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Расчёт срока доставки со склада до города. */
class DeliveryTimeCalculatorTest extends TestCase
{
    use RefreshDatabase;

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

    private function createCity(): City
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);

        return City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]);
    }

    private function createScheduleForToday(Warehouse $warehouse): void
    {
        DeliverySchedule::create([
            'warehouse_id' => $warehouse->id,
            'day_of_week' => now()->dayOfWeekIso - 1,
            'cutoff_time' => '18:00',
            'days_before' => 2,
            'days_after' => 5,
        ]);
    }
}
