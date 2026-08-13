<?php

namespace App\Jobs\CatalogImport;

use App\Enums\Import\ImportType;
use App\Models\System\ProductImport;
use App\Services\Import\ChunkRowProcessorFactory;
use App\Services\Import\ImportStatusUpdater;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;

/** Обработка одного JSON-чанка: создание/обновление товаров и остатков (tire|wheel). */
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
        public readonly ImportType $importType,
    ) {}

    public function handle(
        ChunkRowProcessorFactory $factory,
        Connection $connection,
    ): void {
        $data = $this->readChunkFile();
        $processor = $factory->create($this->importType);

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $stockIds = [];

        foreach ($data['rows'] as $rowIndex => $rowData) {
            try {
                $result = $processor->process($rowData);
                if ($result->created) {
                    $created++;
                } else {
                    $updated++;
                }
                if ($result->stockId !== null) {
                    $stockIds[] = $result->stockId;
                }
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

        $this->updateCounters($created, $updated, $failed);
        $this->appendErrors($errors);
        $this->mergeStockIds($stockIds, $connection);
        $this->deleteChunkFile();
    }

    /**
     * Накопить затронутые импортом остатки — их пересчитает PopulateCatalogPrices
     * после завершения батча. Чанки идут параллельно, поэтому read-modify-write
     * под блокировкой строки.
     *
     * @param  int[]  $stockIds
     */
    private function mergeStockIds(array $stockIds, Connection $connection): void
    {
        if ($stockIds === []) {
            return;
        }

        $connection->transaction(function () use ($stockIds) {
            $import = ProductImport::where('id', $this->importId)->lockForUpdate()->first();
            if (! $import) {
                return;
            }

            $merged = array_values(array_unique(array_merge($import->affected_stock_ids ?? [], $stockIds)));
            $import->update(['affected_stock_ids' => $merged]);
        });
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

    /** Laravel failed()-hook не поддерживает DI — только через app(). */
    public function failed(\Throwable $e): void
    {
        app(ImportStatusUpdater::class)->markFailed($this->importId, $e, 'ChunkJob');
    }
}
