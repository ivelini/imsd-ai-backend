<?php

namespace Tests\Feature\Import;

use App\DTOs\Catalog\Tire\EuroLabel;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Services\Import\TireRowProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Результат обработки строки шины: created + id затронутого остатка. */
class TireRowProcessorTest extends TestCase
{
    use RefreshDatabase;

    public function test_tire_processor_returns_stock_id_when_warehouse_present(): void
    {
        $result = app(TireRowProcessor::class)->process([
            'ean' => 'T-001',
            'brand_name' => 'Brand A',
            'season_raw' => 'зимняя',
            'name' => 'Tire A',
            'width' => '205',
            'profile' => '55',
            'diameter' => '16',
            'load_speed_index' => '91T',
            'warehouse_name' => 'WH-1',
            'quantity' => '5',
            'purchase_price' => '100',
        ]);

        $this->assertTrue($result->created);
        $this->assertSame(Stock::firstOrFail()->id, $result->stockId);
    }

    public function test_tire_processor_returns_null_stock_id_without_warehouse(): void
    {
        $result = app(TireRowProcessor::class)->process([
            'ean' => 'T-001',
            'brand_name' => 'Brand A',
            'season_raw' => 'зимняя',
            'name' => 'Tire A',
            'width' => '205',
            'profile' => '55',
            'diameter' => '16',
            'load_speed_index' => '91T',
        ]);

        $this->assertTrue($result->created);
        $this->assertNull($result->stockId);
        $this->assertSame(0, Stock::count());
    }

    public function test_processor_persists_descriptions_and_euro_label(): void
    {
        app(TireRowProcessor::class)->process([
            'ean' => 'T-001',
            'brand_name' => 'Brand A',
            'season_raw' => 'зимняя',
            'name' => 'Tire A',
            'width' => '205',
            'profile' => '55',
            'diameter' => '16',
            'load_speed_index' => '91T',
            'description_default' => 'Описание',
            'description_euro_label' => 'D/C/71',
        ]);

        $tire = TireProduct::where('ean', 'T-001')->firstOrFail();

        $this->assertSame(['default' => 'Описание'], json_decode($tire->description, true));
        $this->assertEquals(new EuroLabel('D', 'C', '71'), $tire->euro_label);
    }

    public function test_processor_sets_null_description_without_descriptions(): void
    {
        app(TireRowProcessor::class)->process([
            'ean' => 'T-001',
            'brand_name' => 'Brand A',
            'season_raw' => 'зимняя',
            'name' => 'Tire A',
            'width' => '205',
            'profile' => '55',
            'diameter' => '16',
            'load_speed_index' => '91T',
        ]);

        $tire = TireProduct::where('ean', 'T-001')->firstOrFail();

        $this->assertNull($tire->description);
        $this->assertNull($tire->euro_label);
    }
}
