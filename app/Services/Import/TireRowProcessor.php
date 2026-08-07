<?php

namespace App\Services\Import;

use App\Actions\TireImport\UpsertStock;
use App\Actions\TireImport\UpsertTireProduct;
use App\DTOs\TireImport\ImportTireRow;
use App\DTOs\TireImport\UpsertStockInput;
use App\Models\Catalog\Tire\TireProduct;
use App\Preconditions\TireImport\EnsureEanNotEmpty;

/** Обработка строки чанка шин: DTO → upsert товара + остаток. */
final readonly class TireRowProcessor implements ChunkRowProcessor
{
    public function __construct(
        private UpsertTireProduct $upsertTireProduct,
        private UpsertStock $upsertStock,
        private EnsureEanNotEmpty $ensureEanNotEmpty,
    ) {}

    public function process(array $rowData): bool
    {
        $row = ImportTireRow::fromArray($rowData);

        $this->ensureEanNotEmpty->ensure($row->ean);

        $result = $this->upsertTireProduct->execute($row);

        if ($row->warehouse_name !== null) {
            $tire = TireProduct::where('ean', $row->ean)->firstOrFail();
            $this->upsertStock->execute(new UpsertStockInput(
                stockableType: $tire->getMorphClass(),
                stockableId: $tire->id,
                warehouseName: $row->warehouse_name,
                quantity: $row->quantity,
                purchasePrice: $row->purchase_price,
            ));
        }

        return $result->created;
    }
}
