<?php

namespace App\Services\Import;

use App\Actions\Import\Tire\UpsertStock;
use App\Actions\Import\Wheel\UpsertWheelProduct;
use App\DTOs\Import\RowProcessResult;
use App\DTOs\TireImport\UpsertStockInput;
use App\DTOs\WheelImport\UpsertWheelProductInput;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Preconditions\TireImport\EnsureEanNotEmpty;

/** Обработка строки чанка дисков: маппинг → upsert товара + остаток. */
final readonly class WheelRowProcessor implements ChunkRowProcessor
{
    public function __construct(
        private UpsertWheelProduct $upsertWheelProduct,
        private UpsertStock $upsertStock,
        private EnsureEanNotEmpty $ensureEanNotEmpty,
    ) {}

    public function process(array $rowData): RowProcessResult
    {
        $ean = $rowData['ean'] ?? '';
        $this->ensureEanNotEmpty->ensure($ean);

        $exists = WheelProduct::where('ean', $ean)->exists();
        $originKeys = ['origin_vendor', 'origin_manufacture_country', 'origin_manufacture_year'];

        $this->upsertWheelProduct->execute(new UpsertWheelProductInput(
            ean: $ean,
            brandName: $rowData['brand_name'] ?? '',
            name: $rowData['name'] ?? '',
            countryName: $rowData['country_name'] ?? null,
            color: $rowData['color'] ?? null,
            diameter: isset($rowData['diameter']) ? (int) $rowData['diameter'] : null,
            width: $rowData['width'] ?? null,
            pcd1: $rowData['pcd1'] ?? null,
            pcd2: $rowData['pcd2'] ?? null,
            hubDiameter: $rowData['hub_diameter'] ?? null,
            et: $rowData['et'] ?? null,
            wheelTypeRaw: $rowData['wheel_type_raw'] ?? null,
            description: $this->nullableString($rowData['description'] ?? null),
            descriptionPresent: array_key_exists('description', $rowData),
            originVendor: OriginParser::parse($rowData['origin_vendor'] ?? null),
            originManufactureCountry: OriginParser::parse($rowData['origin_manufacture_country'] ?? null),
            originManufactureYear: OriginParser::parse($rowData['origin_manufacture_year'] ?? null),
            originPresent: $this->hasKey($rowData, $originKeys),
        ));

        $stockId = null;
        if (! empty($rowData['warehouse_name'])) {
            $wheel = WheelProduct::where('ean', $ean)->firstOrFail();
            $stock = $this->upsertStock->execute(new UpsertStockInput(
                stockableType: $wheel->getMorphClass(),
                stockableId: $wheel->id,
                warehouseName: $rowData['warehouse_name'],
                quantity: isset($rowData['quantity']) ? (int) $rowData['quantity'] : null,
                purchasePrice: isset($rowData['purchase_price']) ? (float) $rowData['purchase_price'] : null,
            ));
            $stockId = $stock->id;
        }

        return new RowProcessResult(created: ! $exists, stockId: $stockId);
    }

    private function nullableString(?string $value): ?string
    {
        $v = trim($value ?? '');

        return $v === '' ? null : $v;
    }

    /** Колонка есть в файле, если её ключ присутствует в данных строки (даже со значением null/''). */
    private function hasKey(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return true;
            }
        }

        return false;
    }
}
