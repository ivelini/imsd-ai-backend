<?php

namespace App\Jobs\CatalogImport;

use App\Actions\TireImport\UpsertStock;
use App\Actions\TireImport\UpsertWheelProduct;
use App\DTOs\TireImport\UpsertStockInput;
use App\DTOs\TireImport\UpsertWheelProductInput;
use App\Events\Admin\ImportCompleted;
use App\Models\Catalog\WheelProduct;
use App\Models\System\ProductImport;
use App\Preconditions\TireImport\EnsureEanNotEmpty;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;

/** Обработка одного JSON-чанка дисков: создание/обновление wheel_products и остатков. */
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
        EnsureEanNotEmpty $ensureEanNotEmpty,
    ): void {
        $data = $this->readChunkFile();

        [$created, $updated, $failed, $errors] = $this->processWheelRows(
            $data['rows'],
            $upsertWheel,
            $upsertStock,
            $ensureEanNotEmpty,
        );

        $this->updateCounters($created, $updated, $failed);
        $this->appendErrors($errors);
        $this->deleteChunkFile();
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>}
     */
    private function readChunkFile(): array
    {
        if (! file_exists($this->chunkFilePath)) {
            throw new RuntimeException("Файл чанка не найден: {$this->chunkFilePath}");
        }

        return json_decode(
            file_get_contents($this->chunkFilePath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{int, int, int, array<int, array{ean: string, error: string}>}
     */
    private function processWheelRows(
        array $rows,
        UpsertWheelProduct $upsertWheel,
        UpsertStock $upsertStock,
        EnsureEanNotEmpty $ensureEanNotEmpty,
    ): array {
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $rowData) {
            try {
                $isNew = $this->upsertWheelRow($rowData, $upsertWheel, $ensureEanNotEmpty);

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }

                $this->importWheelStock($rowData, $upsertStock);
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

        return [$created, $updated, $failed, $errors];
    }

    /**
     * @param  array<string, mixed>  $rowData
     */
    private function upsertWheelRow(array $rowData, UpsertWheelProduct $upsertWheel, EnsureEanNotEmpty $ensureEanNotEmpty): bool
    {
        $ean = $rowData['ean'] ?? '';
        $ensureEanNotEmpty->ensure($ean);

        $exists = WheelProduct::where('ean', $ean)->exists();

        $upsertWheel->execute(new UpsertWheelProductInput(
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

        return ! $exists;
    }

    /**
     * @param  array<string, mixed>  $rowData
     */
    private function importWheelStock(array $rowData, UpsertStock $upsertStock): void
    {
        if (empty($rowData['warehouse_name'])) {
            return;
        }

        $wheel = WheelProduct::where('ean', $rowData['ean'])->firstOrFail();
        $upsertStock->execute(new UpsertStockInput(
            stockableType: $wheel->getMorphClass(),
            stockableId: $wheel->id,
            warehouseName: $rowData['warehouse_name'],
            quantity: isset($rowData['quantity']) ? (int) $rowData['quantity'] : null,
            purchasePrice: isset($rowData['purchase_price']) ? (float) $rowData['purchase_price'] : null,
        ));
    }

    private function updateCounters(int $created, int $updated, int $failed): void
    {
        ProductImport::where('id', $this->importId)
            ->lockForUpdate()
            ->incrementEach([
                'created_rows' => $created,
                'updated_rows' => $updated,
                'failed_rows' => $failed,
                'processed_rows' => $created + $updated + $failed,
            ]);
    }

    /**
     * @param  array<int, array{ean: string, error: string}>  $errors
     */
    private function appendErrors(array $errors): void
    {
        if (empty($errors)) {
            return;
        }

        $import = ProductImport::find($this->importId);
        if (! $import) {
            return;
        }

        $existing = $import->errors ?? [];
        $merged = array_slice(array_merge($existing, $errors), 0, 100);
        $import->update(['errors' => $merged]);
    }

    private function deleteChunkFile(): void
    {
        if (file_exists($this->chunkFilePath)) {
            unlink($this->chunkFilePath);
        }
    }

    public function failed(\Throwable $e): void
    {
        ProductImport::where('id', $this->importId)->update([
            'status' => 'failed',
            'error_message' => 'WheelChunkJob: '.$e->getMessage(),
            'finished_at' => now(),
        ]);

        $import = ProductImport::find($this->importId);
        if ($import) {
            event(new ImportCompleted($import));
        }
    }
}
