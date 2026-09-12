<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Clusters\Catalog\Resources\Products\RelationManagers\StocksRelationManager;
use App\Filament\Clusters\Catalog\Resources\TireProducts\Pages\EditTireProduct;
use App\Filament\Clusters\Catalog\Resources\WheelProducts\Pages\EditWheelProduct;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Остатки товара по складам (FR ADM-4.1.2/4.1.3, ADR 0002): список складов, количества и цен в карточке товара. */
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

    public function test_table_shows_wheel_stocks(): void
    {
        $stock = $this->wheel->stocks()->create([
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 4,
            'purchase_price' => 100,
            'price' => 100,
        ]);

        Livewire::test(StocksRelationManager::class, [
            'ownerRecord' => $this->wheel,
            'pageClass' => EditWheelProduct::class,
        ])
            ->assertCanSeeTableRecords([$stock]);
    }

    private function createStock(Warehouse $warehouse, int $quantity, ?float $purchasePrice = null): Stock
    {
        return $this->tire->stocks()->create([
            'warehouse_id' => $warehouse->id,
            'quantity' => $quantity,
            'purchase_price' => $purchasePrice,
            'price' => $purchasePrice,
        ]);
    }
}
