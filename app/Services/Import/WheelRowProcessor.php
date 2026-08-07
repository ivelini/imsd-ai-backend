<?php

namespace App\Services\Import;

use App\Actions\TireImport\UpsertStock;
use App\Actions\WheelImport\UpsertWheelProduct;
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

    public function process(array $rowData): bool
    {
        $ean = $rowData['ean'] ?? '';
        $this->ensureEanNotEmpty->ensure($ean);

        $exists = WheelProduct::where('ean', $ean)->exists();

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
            supplierName: $rowData['supplier_name'] ?? null,
            description: $rowData['description_vendor'] ?? null,
        ));

        if (! empty($rowData['warehouse_name'])) {
            $wheel = WheelProduct::where('ean', $ean)->firstOrFail();
            $this->upsertStock->execute(new UpsertStockInput(
                stockableType: $wheel->getMorphClass(),
                stockableId: $wheel->id,
                warehouseName: $rowData['warehouse_name'],
                quantity: isset($rowData['quantity']) ? (int) $rowData['quantity'] : null,
                purchasePrice: isset($rowData['purchase_price']) ? (float) $rowData['purchase_price'] : null,
            ));
        }

        return ! $exists;
    }
}
