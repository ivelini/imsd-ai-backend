<?php

namespace Tests\Feature\Catalog;

use App\Actions\Catalog\PopulateCatalogPrices;
use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Promotion\Promotion;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCity;
use Tests\TestCase;

/** Применение активной акции к предрасчитанным ценам города: price со скидкой, base_price без. */
class PromotionPriceTest extends TestCase
{
    use CreatesCity, RefreshDatabase;

    private Warehouse $warehouse;

    private TireProduct $tire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->tire = TireProduct::factory()->create(['brand_id' => Brand::factory()->create()->id]);
    }

    public function test_catalog_price_includes_active_promotion(): void
    {
        $city = $this->createCity();
        $stock = $this->createStock(price: 1000);
        $this->createPromotion(type: 'percent', value: 10, promotableType: 'tire', promotableId: $this->tire->id);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $row = $this->catalogPrice($stock, $city);
        $this->assertSame(900.0, (float) $row->price);
        $this->assertSame(1000.0, (float) $row->base_price);
    }

    public function test_base_price_equals_price_without_promotion(): void
    {
        $city = $this->createCity();
        $stock = $this->createStock(price: 1000);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $row = $this->catalogPrice($stock, $city);
        $this->assertSame(1000.0, (float) $row->price);
        $this->assertSame(1000.0, (float) $row->base_price);
    }

    public function test_expired_promotion_does_not_affect_price(): void
    {
        $city = $this->createCity();
        $stock = $this->createStock(price: 1000);
        $this->createPromotion(
            type: 'percent',
            value: 10,
            promotableType: 'tire',
            promotableId: $this->tire->id,
            startsAt: now()->subMonth(),
            endsAt: now()->subDay(),
        );

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $row = $this->catalogPrice($stock, $city);
        $this->assertSame(1000.0, (float) $row->price);
    }

    public function test_brand_promotion_applies_to_its_products(): void
    {
        $city = $this->createCity();
        $stock = $this->createStock(price: 1000);
        $this->createPromotion(
            type: 'fixed',
            value: 150,
            promotableType: 'brand',
            promotableId: $this->tire->brand_id,
        );

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $row = $this->catalogPrice($stock, $city);
        $this->assertSame(850.0, (float) $row->price);
        $this->assertSame(1000.0, (float) $row->base_price);
    }

    public function test_sync_command_recalculates_promoted_products(): void
    {
        $city = $this->createCity();
        $stock = $this->createStock(price: 1000);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);
        $this->assertSame(1000.0, (float) $this->catalogPrice($stock, $city)->price);

        // Акция началась уже после первого расчёта — команда закрывает границу интервала
        $this->createPromotion(type: 'percent', value: 20, promotableType: 'tire', promotableId: $this->tire->id);

        $this->artisan('promotions:sync')->assertSuccessful();

        $this->assertSame(800.0, (float) $this->catalogPrice($stock, $city)->price);
    }

    private function createStock(float $price): Stock
    {
        return Stock::create([
            'stockable_type' => $this->tire->getMorphClass(),
            'stockable_id' => $this->tire->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
            'purchase_price' => $price,
            'price' => $price,
        ]);
    }

    private function createPromotion(
        string $type,
        ?float $value,
        ?string $promotableType = null,
        ?int $promotableId = null,
        mixed $startsAt = null,
        mixed $endsAt = null,
    ): Promotion {
        return Promotion::create([
            'name' => 'Акция',
            'type' => $type,
            'value' => $value,
            'starts_at' => $startsAt ?? now()->subDay(),
            'ends_at' => $endsAt ?? now()->addDay(),
            'promotable_type' => $promotableType,
            'promotable_id' => $promotableId,
        ]);
    }

    private function catalogPrice(Stock $stock, City $city): CatalogPrice
    {
        return CatalogPrice::where('stock_id', $stock->id)->where('city_id', $city->id)->firstOrFail();
    }
}
