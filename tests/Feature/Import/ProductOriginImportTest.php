<?php

namespace Tests\Feature\Import;

use App\Actions\Import\Tire\UpsertTireProduct;
use App\Actions\Import\Wheel\UpsertWheelProduct;
use App\DTOs\Catalog\OriginInfo;
use App\DTOs\TireImport\ImportTireRow;
use App\DTOs\WheelImport\UpsertWheelProductInput;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Origin\ProductOrigin;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Импорт origin-колонок и description в product_origins / product_models.description. */
class ProductOriginImportTest extends TestCase
{
    use RefreshDatabase;

    private function tireRow(
        string $ean,
        bool $originPresent = false,
        bool $descriptionPresent = false,
    ): ImportTireRow {
        return new ImportTireRow(
            ean: $ean,
            brand_name: 'Nokian',
            season_raw: 'летняя',
            country_name: null,
            name: 'Hakka',
            width: 215,
            profile: 60,
            diameter: '16',
            load_speed_index: null,
            is_runflat_raw: null,
            is_studded_raw: null,
            warehouse_name: null,
            quantity: null,
            purchase_price: null,
            minimum_market_price: null,
            euroLabel: null,
            description: $descriptionPresent ? 'Описание модели' : null,
            description_present: $descriptionPresent,
            origin_vendor: $originPresent ? new OriginInfo('Shandong Haohua Tire', null) : null,
            origin_manufacture_country: $originPresent ? new OriginInfo('100% Китай', '<p>Данные о стране.</p>') : null,
            origin_manufacture_year: $originPresent ? new OriginInfo('2024-2025', '<p>Дата выпуска.</p>') : null,
            origin_present: $originPresent,
            promos: [],
        );
    }

    public function test_tire_import_fills_origin(): void
    {
        app(UpsertTireProduct::class)->execute($this->tireRow(ean: 'TIRE-1', originPresent: true));

        $origin = ProductOrigin::firstOrFail();
        $this->assertSame('Shandong Haohua Tire', $origin->vendor?->badge);
        $this->assertSame('100% Китай', $origin->manufacture_country?->badge);
        $this->assertSame('2024-2025', $origin->manufacture_year?->badge);

        $this->assertDatabaseHas('tire_products', ['ean' => 'TIRE-1', 'origin_id' => $origin->id]);
    }

    public function test_tire_reimport_without_origin_keeps_origin(): void
    {
        $upsert = app(UpsertTireProduct::class);
        $upsert->execute($this->tireRow(ean: 'TIRE-2', originPresent: true));

        $originId = TireProduct::where('ean', 'TIRE-2')->value('origin_id');
        $this->assertNotNull($originId);

        $upsert->execute($this->tireRow(ean: 'TIRE-2'));

        $this->assertDatabaseHas('tire_products', ['ean' => 'TIRE-2', 'origin_id' => $originId]);
    }

    public function test_wheel_import_origin_vendor_only(): void
    {
        app(UpsertWheelProduct::class)->execute(new UpsertWheelProductInput(
            ean: 'WHEEL-1',
            brandName: 'Brand X',
            name: 'Диск Model X',
            countryName: null,
            color: null,
            diameter: 16,
            width: '7',
            pcd1: null,
            pcd2: null,
            hubDiameter: null,
            et: null,
            wheelTypeRaw: null,
            description: null,
            descriptionPresent: false,
            originVendor: new OriginInfo('Shandong Haohua Tire', null),
            originManufactureCountry: null,
            originManufactureYear: null,
            originPresent: true,
        ));

        $origin = ProductOrigin::firstOrFail();
        $this->assertSame('Shandong Haohua Tire', $origin->vendor?->badge);
        $this->assertNull($origin->manufacture_country);
        $this->assertNull($origin->manufacture_year);

        $this->assertDatabaseHas('wheel_products', ['ean' => 'WHEEL-1', 'origin_id' => $origin->id]);
    }

    public function test_tire_import_sets_model_description(): void
    {
        app(UpsertTireProduct::class)->execute($this->tireRow(ean: 'TIRE-3', descriptionPresent: true));

        $this->assertSame('Описание модели', ProductModel::where('name', 'Hakka')->firstOrFail()->description);
    }

    public function test_reimport_without_description_keeps_model_description(): void
    {
        $upsert = app(UpsertTireProduct::class);
        $upsert->execute($this->tireRow(ean: 'TIRE-4', descriptionPresent: true));
        $upsert->execute($this->tireRow(ean: 'TIRE-4'));

        $this->assertSame('Описание модели', ProductModel::where('name', 'Hakka')->firstOrFail()->description);
    }

    public function test_wheel_import_sets_model_description(): void
    {
        app(UpsertWheelProduct::class)->execute(new UpsertWheelProductInput(
            ean: 'WHEEL-2',
            brandName: 'Brand X',
            name: 'Диск Model X',
            countryName: null,
            color: null,
            diameter: 16,
            width: '7',
            pcd1: null,
            pcd2: null,
            hubDiameter: null,
            et: null,
            wheelTypeRaw: null,
            description: 'Описание диска',
            descriptionPresent: true,
            originVendor: null,
            originManufactureCountry: null,
            originManufactureYear: null,
            originPresent: false,
        ));

        $this->assertSame('Описание диска', ProductModel::where('name', 'Model X')->firstOrFail()->description);
        $this->assertDatabaseMissing('product_origins', ['id' => 1]);
    }
}
