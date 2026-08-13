<?php

namespace Tests\Feature\Services\Catalog;

use App\Models\Delivery\City;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\Region;
use App\Services\Catalog\DeliveryCostCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Стоимость доставки по городу из city_price_rules. */
class DeliveryCostCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_returns_markup(): void
    {
        $city = $this->createCity();
        CityPriceRule::create(['city_id' => $city->id, 'price_from' => 0, 'price_to' => 5000, 'markup' => 300]);

        $this->assertSame(300.0, app(DeliveryCostCalculator::class)->calculate($city->id, 1500.0));
    }

    public function test_calculate_returns_null_when_no_covering_rule(): void
    {
        $city = $this->createCity();
        CityPriceRule::create(['city_id' => $city->id, 'price_from' => 6000, 'price_to' => 10000, 'markup' => 500]);

        $this->assertNull(app(DeliveryCostCalculator::class)->calculate($city->id, 1500.0));
    }

    public function test_calculate_prefers_smallest_price_from_on_overlap(): void
    {
        $city = $this->createCity();
        CityPriceRule::create(['city_id' => $city->id, 'price_from' => 100, 'price_to' => 1000, 'markup' => 200]);
        CityPriceRule::create(['city_id' => $city->id, 'price_from' => 50, 'price_to' => 2000, 'markup' => 300]);

        $this->assertSame(300.0, app(DeliveryCostCalculator::class)->calculate($city->id, 500.0));
    }

    private function createCity(): City
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);

        return City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]);
    }
}
