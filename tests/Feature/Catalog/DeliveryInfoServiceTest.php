<?php

namespace Tests\Feature\Catalog;

use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\City;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\DeliverySchedule;
use App\Models\Delivery\Region;
use App\Services\Catalog\DeliveryInfoService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Блок delivery для карточки товара: срок + наценка города. */
class DeliveryInfoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_enrich_product_sets_markup_from_city_rule(): void
    {
        Carbon::setTestNow(now()->startOfDay()->addHours(10));

        $warehouse = Warehouse::factory()->create();
        DeliverySchedule::create([
            'warehouse_id' => $warehouse->id,
            'day_of_week' => now()->dayOfWeekIso - 1,
            'cutoff_time' => '18:00',
            'days_before' => 2,
            'days_after' => 5,
        ]);

        $tire = TireProduct::factory()->create();
        $stock = Stock::create([
            'stockable_type' => $tire->getMorphClass(),
            'stockable_id' => $tire->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'purchase_price' => 100,
            'price' => 500,
        ]);
        $tire->setRelation('stocks', new Collection([$stock]));

        $city = $this->createCity();
        CityPriceRule::create(['city_id' => $city->id, 'price_from' => 0, 'price_to' => 5000, 'markup' => 300]);

        app(DeliveryInfoService::class)->enrichProduct($tire, $city->id);

        $delivery = $tire->getRelation('delivery');
        $this->assertSame(300.0, $delivery['markup']);
        $this->assertSame(2, $delivery['delivery_days']);
    }

    private function createCity(): City
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);

        return City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]);
    }
}
