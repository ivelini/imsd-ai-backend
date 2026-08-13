<?php

namespace Tests\Feature\Delivery;

use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CityPriceRule;
use App\Services\Delivery\DeliveryInfoService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesCity;
use Tests\TestCase;

/** Блок delivery для карточки товара: срок + наценка города. */
class DeliveryInfoServiceTest extends TestCase
{
    use CreatesCity, RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_enrich_product_sets_markup_from_city_rule(): void
    {
        $tire = $this->createProductWithStock();

        $city = $this->createCity();
        CityPriceRule::create(['city_id' => $city->id, 'price_from' => 0, 'price_to' => 5000, 'markup' => 300]);

        app(DeliveryInfoService::class)->enrichProduct($tire, $city->id);

        $delivery = $tire->getRelation('delivery');
        $this->assertSame(300.0, $delivery['markup']);
        $this->assertSame(2, $delivery['delivery_days']);
    }

    public function test_enrich_product_prefers_smallest_price_from_on_overlap(): void
    {
        $tire = $this->createProductWithStock();

        $city = $this->createCity();
        CityPriceRule::create(['city_id' => $city->id, 'price_from' => 100, 'price_to' => 1000, 'markup' => 200]);
        CityPriceRule::create(['city_id' => $city->id, 'price_from' => 50, 'price_to' => 2000, 'markup' => 300]);

        app(DeliveryInfoService::class)->enrichProduct($tire, $city->id);

        $this->assertSame(300.0, $tire->getRelation('delivery')['markup']);
    }

    public function test_enrich_product_markup_null_when_no_city_rule(): void
    {
        $tire = $this->createProductWithStock();

        $city = $this->createCity();
        CityPriceRule::create(['city_id' => $city->id, 'price_from' => 6000, 'price_to' => 10000, 'markup' => 500]);

        app(DeliveryInfoService::class)->enrichProduct($tire, $city->id);

        $this->assertNull($tire->getRelation('delivery')['markup']);
    }

    private function createProductWithStock(): TireProduct
    {
        Carbon::setTestNow(now()->startOfDay()->addHours(10));

        $warehouse = Warehouse::factory()->create();
        $this->createScheduleForToday($warehouse);

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

        return $tire;
    }
}
