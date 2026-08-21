<?php

namespace App\Services\Import;

use App\Actions\Import\Tire\UpsertStock;
use App\Actions\Import\Tire\UpsertTireProduct;
use App\DTOs\Import\RowProcessResult;
use App\DTOs\TireImport\UpsertStockInput;
use App\Models\Catalog\Tire\TireProduct;
use App\Preconditions\TireImport\EnsureEanNotEmpty;
use App\Services\TireImport\RowMapper;

/** Обработка строки чанка шин: DTO → upsert товара + остаток. */
final readonly class TireRowProcessor implements ChunkRowProcessor
{
    public function __construct(
        private UpsertTireProduct $upsertTireProduct,
        private UpsertStock $upsertStock,
        private EnsureEanNotEmpty $ensureEanNotEmpty,
        private RowMapper $rowMapper,
    ) {}

    public function process(array $rowData): RowProcessResult
    {
        $row = $this->rowMapper->map($rowData);

        $this->ensureEanNotEmpty->ensure($row->ean);

        $result = $this->upsertTireProduct->execute($row);

        $stockId = null;
        if ($row->warehouse_name !== null) {
            $tire = TireProduct::where('ean', $row->ean)->firstOrFail();
            $stock = $this->upsertStock->execute(new UpsertStockInput(
                stockableType: $tire->getMorphClass(),
                stockableId: $tire->id,
                warehouseName: $row->warehouse_name,
                quantity: $row->quantity,
                purchasePrice: $row->purchase_price,
            ));
            $stockId = $stock->id;
        }

        return new RowProcessResult(created: $result->created, stockId: $stockId);
    }
}
