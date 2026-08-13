<?php

namespace App\Jobs\CatalogImport;

use App\Actions\Catalog\PopulateCatalogPrices;
use App\Actions\Import\Tire\ParseImportFile;
use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\DTOs\Import\ImportMasterJobInput;
use App\DTOs\TireImport\ParseImportFileInput;
use App\Models\System\ProductImport;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;
use App\Services\Import\ImportStatusUpdater;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;

/** Оркестратор импорта tire/wheel: парсинг XLSX → чанки → dispatch ChunkJob. */
final class ImportMasterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        private readonly ImportMasterJobInput $input,
    ) {}

    public function handle(ParseImportFile $parseAction, ImportStatusUpdater $statusUpdater): void
    {
        $statusUpdater->markProcessing($this->input->importId);

        try {
            $result = $this->parseFile($parseAction);

            ProductImport::where('id', $this->input->importId)
                ->update(['total_rows' => $result->totalRows]);

            if (empty($result->chunkFilePaths)) {
                $statusUpdater->markCompleted($this->input->importId);

                return;
            }

            $this->dispatchBatch($result->chunkFilePaths, $statusUpdater);
        } catch (\Throwable $e) {
            $statusUpdater->markFailed($this->input->importId, $e);
            throw $e;
        }
    }

    private function parseFile(ParseImportFile $parseAction): object
    {
        return $parseAction->execute(new ParseImportFileInput(
            filePath: $this->input->filePath,
            batchId: (string) $this->input->importId,
            chunkSize: $this->input->chunkSize,
            chunkDir: storage_path('app/'.trim($this->input->chunkPath, '/')),
            requiredColumns: $this->input->requiredColumns,
            columnMap: $this->input->columnMap,
        ));
    }

    /**
     * @param  string[]  $chunkFilePaths
     */
    private function dispatchBatch(array $chunkFilePaths, ImportStatusUpdater $statusUpdater): void
    {
        $importType = $this->input->importType;
        $importId = $this->input->importId;

        $batch = array_map(
            fn (string $chunkPath) => new ChunkJob(
                importId: $importId,
                chunkFilePath: $chunkPath,
                importType: $importType,
            ),
            $chunkFilePaths,
        );

        Bus::batch($batch)
            ->name("{$importType->value}-import-{$importId}")
            ->finally(static function () use ($importId, $statusUpdater) {
                $statusUpdater->completeIfProcessing($importId);

                // Пересчитываем только остатки, затронутые этим импортом
                $stockIds = ProductImport::find($importId)->affected_stock_ids ?? [];
                if ($stockIds !== []) {
                    app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput(stockIds: $stockIds));
                    // upsert() в catalog_prices не триггерит Eloquent-события — инвалидация вручную
                    app(TireFilterValuesCacheService::class)->forget();
                }
            })
            ->dispatch();
    }
}
