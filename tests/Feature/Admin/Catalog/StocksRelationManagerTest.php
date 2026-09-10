<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Clusters\Catalog\Resources\Products\RelationManagers\StocksRelationManager;
use App\Filament\Clusters\Catalog\Resources\TireProducts\Pages\EditTireProduct;
use App\Filament\Clusters\Catalog\Resources\WheelProducts\Pages\EditWheelProduct;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Models\Delivery\City;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Остатки товара по складам: правка количества и цен, пересчёт цен города (FR ADM-4.1.2/4.1.3, ADR 0002). */
class StocksRelationManagerTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private TireProduct $tire;

    private WheelProduct $wheel;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');

        $brand = Brand::factory()->create();
        $this->tire = TireProduct::factory()->create(['brand_id' => $brand->id]);
        $this->wheel = WheelProduct::factory()->create(['brand_id' => $brand->id]);
        $this->warehouse = Warehouse::factory()->create(['name' => 'Склад']);
    }

    public function test_table_shows_product_stocks(): void
    {
        $second = Warehouse::factory()->create(['name' => 'Второй склад']);
        $first = $this->createStock($this->warehouse, quantity: 5, purchasePrice: 100);
        $other = $this->createStock($second, quantity: 12, purchasePrice: 200);

        Livewire::test(StocksRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->assertCanSeeTableRecords([$first, $other]);
    }

    public function test_create_stock_computes_sale_price_by_warehouse_rule(): void
    {
        WarehouseMarkupRule::create([
            'warehouse_id' => $this->warehouse->id,
            'price_from' => 0,
            'price_to' => 500,
            'coefficient' => 1.5,
        ]);

        Livewire::test(StocksRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('create', data: [
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 5,
                'purchase_price' => 100,
            ])
            ->assertHasNoActionErrors();

        $stock = Stock::where('stockable_id', $this->tire->id)->firstOrFail();

        $this->assertSame('150.00', $stock->price);
        $this->assertSame($this->tire->getMorphClass(), $stock->stockable_type);
    }

    public function test_create_stock_without_rule_uses_purchase_price(): void
    {
        Livewire::test(StocksRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('create', data: [
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 3,
                'purchase_price' => 100,
            ])
            ->assertHasNoActionErrors();

        $stock = Stock::where('stockable_id', $this->tire->id)->firstOrFail();

        $this->assertSame('100.00', $stock->price);
    }

    public function test_create_stock_rejects_duplicate_warehouse(): void
    {
        $this->createStock($this->warehouse, quantity: 5, purchasePrice: 100);

        Livewire::test(StocksRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('create', data: [
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 7,
            ])
            ->assertHasTableActionErrors(['warehouse_id']);

        $this->assertSame(1, Stock::where('stockable_id', $this->tire->id)->count());
    }

    public function test_create_stock_recalculates_catalog_prices(): void
    {
        $this->createCityWithMarkup(50);
        $this->addMarkupRule(coefficient: 1.5);

        Livewire::test(StocksRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('create', data: [
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 5,
                'purchase_price' => 100,
            ])
            ->assertHasNoActionErrors();

        $stock = Stock::where('stockable_id', $this->tire->id)->firstOrFail();

        $this->assertDatabaseHas('catalog_prices', ['stock_id' => $stock->id, 'price' => 200]);
    }

    public function test_update_stock_quantity_only_keeps_sale_price(): void
    {
        $stock = $this->createStock($this->warehouse, quantity: 5, purchasePrice: 100);
        $stock->update(['price' => 150]);

        Livewire::test(StocksRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('edit', $stock, data: [
                'quantity' => 9,
                'purchase_price' => 100,
                'price' => 150,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('150.00', $stock->refresh()->price);
    }

    public function test_update_stock_purchase_price_recalculates_sale_price(): void
    {
        $this->createCityWithMarkup(50);
        $this->addMarkupRule(coefficient: 1.5);

        $stock = $this->createStock($this->warehouse, quantity: 5, purchasePrice: 100);
        $stock->update(['price' => 150]);

        Livewire::test(StocksRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('edit', $stock, data: [
                'quantity' => 5,
                'purchase_price' => 200,
                'price' => 300,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('300.00', $stock->refresh()->price);
        $this->assertDatabaseHas('catalog_prices', ['stock_id' => $stock->id, 'price' => 350]);
    }

    public function test_update_stock_allows_manual_sale_price(): void
    {
        $this->createCityWithMarkup(50);
        $this->addMarkupRule(coefficient: 1.5);

        $stock = $this->createStock($this->warehouse, quantity: 5, purchasePrice: 100);
        $stock->update(['price' => 150]);

        Livewire::test(StocksRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('edit', $stock, data: [
                'quantity' => 5,
                'purchase_price' => 100,
                'price' => 999,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('999.00', $stock->refresh()->price);
        $this->assertDatabaseHas('catalog_prices', ['stock_id' => $stock->id, 'price' => 1049]);
    }

    public function test_delete_stock_removes_row_and_catalog_prices(): void
    {
        $this->createCityWithMarkup(50);

        $stock = $this->createStock($this->warehouse, quantity: 5, purchasePrice: 100);

        Livewire::test(StocksRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('delete', $stock);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
        $this->assertDatabaseMissing('catalog_prices', ['stock_id' => $stock->id]);
    }

    public function test_stocks_relation_manager_works_for_wheel(): void
    {
        Livewire::test(StocksRelationManager::class, [
            'ownerRecord' => $this->wheel,
            'pageClass' => EditWheelProduct::class,
        ])
            ->callTableAction('create', data: [
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 4,
                'purchase_price' => 100,
            ])
            ->assertHasNoActionErrors();

        $stock = Stock::where('stockable_id', $this->wheel->id)->firstOrFail();

        $this->assertSame($this->wheel->getMorphClass(), $stock->stockable_type);
    }

    private function createStock(Warehouse $warehouse, int $quantity, ?float $purchasePrice = null): Stock
    {
        return Stock::create([
            'stockable_type' => $this->tire->getMorphClass(),
            'stockable_id' => $this->tire->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $quantity,
            'purchase_price' => $purchasePrice,
            'price' => $purchasePrice,
        ]);
    }

    private function addMarkupRule(float $coefficient): void
    {
        WarehouseMarkupRule::create([
            'warehouse_id' => $this->warehouse->id,
            'price_from' => 0,
            'price_to' => 100500,
            'coefficient' => $coefficient,
        ]);
    }

    private function createCityWithMarkup(float $markup): City
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        $city = City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]);

        CityPriceRule::create([
            'city_id' => $city->id,
            'price_from' => 0,
            'price_to' => 100500,
            'markup' => $markup,
        ]);

        return $city;
    }
}
