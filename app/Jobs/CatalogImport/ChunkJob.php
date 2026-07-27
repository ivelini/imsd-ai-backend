<?php

namespace App\Jobs\CatalogImport;

use App\Actions\TireImport\UpsertStock;
use App\Actions\TireImport\UpsertTireProduct;
use App\DTOs\TireImport\ImportTireRow;
use App\DTOs\TireImport\UpsertStockInput;
use App\Models\Catalog\TireProduct;
use App\Models\System\ProductImport;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;

/** Обработка одного JSON-чанка: создание/обновление товаров и остатков. */
final class ChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 300;

    public int $tries = 3;

    /** @var int[] */
    public array $backoff = [5, 30];

    public function __construct(
        public readonly int $importId,
        public readonly string $chunkFilePath,
    ) {}

    public function handle(
        UpsertTireProduct $upsertTireProduct,
        UpsertStock $upsertStock,
    ): void {
        if (! file_exists($this->chunkFilePath)) {
            throw new RuntimeException("Файл чанка не найден: {$this->chunkFilePath}");
        }

        $data = json_decode(file_get_contents($this->chunkFilePath), true, 512, JSON_THROW_ON_ERROR);
        $rows = $data['rows'] ?? [];

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $rowData) {
            try {
                $row = ImportTireRow::fromArray($rowData);

                $result = $upsertTireProduct->execute($row);

                if ($result->created) {
                    $created++;
                } else {
                    $updated++;
                }

                if ($row->warehouse_name !== null) {
                    $tire = TireProduct::where('ean', $row->ean)->firstOrFail();
                    $input = new UpsertStockInput(
                        stockableType: $tire->getMorphClass(),
                        stockableId: $tire->id,
                        warehouseName: $row->warehouse_name,
                        quantity: $row->quantity,
                        purchasePrice: $row->purchase_price,
                    );
                    $upsertStock->execute($input);
                }

            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $rowIndex + 1,
                    'ean' => $rowData['ean'] ?? 'N/A',
                    'error' => $e->getMessage(),
                ];

                if (count($errors) > 100) {
                    break;
                }
            }
        }

        ProductImport::where('id', $this->importId)
            ->lockForUpdate()
            ->incrementEach([
                'created_rows' => $created,
                'updated_rows' => $updated,
                'failed_rows' => $failed,
                'processed_rows' => $created + $updated + $failed,
            ]);

        if (! empty($errors)) {
            $import = ProductImport::find($this->importId);
            if ($import) {
                $existing = $import->errors ?? [];
                $merged = array_slice(array_merge($existing, $errors), 0, 100);
                $import->update(['errors' => $merged]);
            }
        }

        @unlink($this->chunkFilePath);
    }

    public function failed(\Throwable $e): void
    {
        ProductImport::where('id', $this->importId)->update([
            'status' => 'failed',
            'error_message' => 'ChunkJob: '.$e->getMessage(),
            'finished_at' => now(),
        ]);
    }
}
