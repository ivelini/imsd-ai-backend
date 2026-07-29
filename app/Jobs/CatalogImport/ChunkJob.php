<?php

namespace App\Jobs\CatalogImport;

use App\Actions\TireImport\UpsertStock;
use App\Actions\TireImport\UpsertTireProduct;
use App\DTOs\TireImport\ImportTireRow;
use App\DTOs\TireImport\UpsertStockInput;
use App\Models\Catalog\TireProduct;
use App\Models\System\ProductImport;
use App\Preconditions\TireImport\EnsureEanNotEmpty;
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
        EnsureEanNotEmpty $ensureEanNotEmpty,
    ): void {
        $data = $this->readChunkFile();

        [$created, $updated, $failed, $errors] = $this->processRows(
            $data['rows'],
            $upsertTireProduct,
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
     * @return array{int, int, int, array<int, array{row: int, ean: string, error: string}>}
     */
    private function processRows(
        array $rows,
        UpsertTireProduct $upsertTireProduct,
        UpsertStock $upsertStock,
        EnsureEanNotEmpty $ensureEanNotEmpty,
    ): array {
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $rowData) {
            try {
                $row = ImportTireRow::fromArray($rowData);

                $ensureEanNotEmpty->ensure($row->ean);

                $result = $upsertTireProduct->execute($row);

                if ($result->created) {
                    $created++;
                } else {
                    $updated++;
                }

                $this->importStock($row, $upsertStock);
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $rowIndex + 1,
                    'ean' => $rowData['ean'] ?? 'N/A',
                    'error' => $e->getMessage(),
                ];

                // Прерываем при критическом числе ошибок — данные вероятно повреждены
                if (count($errors) > 100) {
                    break;
                }
            }
        }

        return [$created, $updated, $failed, $errors];
    }

    private function importStock(ImportTireRow $row, UpsertStock $upsertStock): void
    {
        if ($row->warehouse_name === null) {
            return;
        }

        $tire = TireProduct::where('ean', $row->ean)->firstOrFail();
        $upsertStock->execute(new UpsertStockInput(
            stockableType: $tire->getMorphClass(),
            stockableId: $tire->id,
            warehouseName: $row->warehouse_name,
            quantity: $row->quantity,
            purchasePrice: $row->purchase_price,
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
     * @param  array<int, array{row: int, ean: string, error: string}>  $errors
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
        // Храним не более 100 ошибок — остальное неинформативный шум
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
            'error_message' => 'ChunkJob: '.$e->getMessage(),
            'finished_at' => now(),
        ]);
    }
}
