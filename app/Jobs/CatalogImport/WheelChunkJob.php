<?php

namespace App\Jobs\CatalogImport;

use App\Actions\TireImport\UpsertStock;
use App\Actions\TireImport\UpsertWheelProduct;
use App\DTOs\TireImport\UpsertStockInput;
use App\Models\Catalog\WheelProduct;
use App\Models\System\ProductImport;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;

/** Обработка чанка дисков: создание/обновление wheel_products и остатков. */
final class WheelChunkJob implements ShouldQueue
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
        UpsertWheelProduct $upsertWheel,
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

        foreach ($rows as $rowData) {
            try {
                $exists = WheelProduct::where('ean', $rowData['ean'] ?? '')->exists();

                $upsertWheel->execute(
                    ean: $rowData['ean'] ?? '',
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
                );

                if ($exists) {
                    $updated++;
                } else {
                    $created++;
                }

                if (! empty($rowData['warehouse_name'])) {
                    $wheel = WheelProduct::where('ean', $rowData['ean'])->firstOrFail();
                    $input = new UpsertStockInput(
                        stockableType: $wheel->getMorphClass(),
                        stockableId: $wheel->id,
                        warehouseName: $rowData['warehouse_name'],
                        quantity: isset($rowData['quantity']) ? (int) $rowData['quantity'] : null,
                        purchasePrice: isset($rowData['purchase_price']) ? (float) $rowData['purchase_price'] : null,
                    );
                    $upsertStock->execute($input);
                }

            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
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
            'error_message' => 'WheelChunkJob: '.$e->getMessage(),
            'finished_at' => now(),
        ]);
    }
}
