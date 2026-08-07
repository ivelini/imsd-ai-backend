<?php

namespace App\Jobs\CatalogImport;

use App\Actions\Catalog\PopulateCatalogPrices;
use App\Actions\TireImport\ParseImportFile;
use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\DTOs\TireImport\ParseImportFileInput;
use App\Events\Admin\ImportCompleted;
use App\Models\System\ProductImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;

/** Оркестратор импорта: парсинг XLSX → чанки → dispatch ChunkJob. */
final class MasterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public readonly int $importId,
        public readonly string $filePath,
        private readonly int $chunkSize,
        private readonly string $chunkPath,
    ) {}

    public function handle(ParseImportFile $parseAction): void
    {
        $import = ProductImport::findOrFail($this->importId);
        $this->markImportProcessing($import);

        try {
            $result = $this->parseFile($parseAction);

            $import->update(['total_rows' => $result->totalRows]);

            if (empty($result->chunkFilePaths)) {
                $this->markImportCompleted($import);

                return;
            }

            $this->dispatchBatch($result->chunkFilePaths);
        } catch (\Throwable $e) {
            $this->markImportFailed($import, $e);
            throw $e;
        }
    }

    private function markImportProcessing(ProductImport $import): void
    {
        $import->update(['status' => 'processing', 'started_at' => now()]);
    }

    private function markImportCompleted(ProductImport $import): void
    {
        $import->update(['status' => 'completed', 'finished_at' => now()]);
        event(new ImportCompleted($import));
    }

    private function markImportFailed(ProductImport $import, \Throwable $e): void
    {
        $import->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'finished_at' => now(),
        ]);
        event(new ImportCompleted($import));
    }

    private function parseFile(ParseImportFile $parseAction): object
    {
        return $parseAction->execute(new ParseImportFileInput(
            filePath: $this->filePath,
            batchId: (string) $this->importId,
            chunkSize: $this->chunkSize,
            chunkDir: storage_path('app/'.trim($this->chunkPath, '/')),
        ));
    }

    /**
     * @param  string[]  $chunkFilePaths
     */
    private function dispatchBatch(array $chunkFilePaths): void
    {
        $batch = array_map(
            fn (string $chunkPath) => new ChunkJob(
                importId: $this->importId,
                chunkFilePath: $chunkPath,
            ),
            $chunkFilePaths,
        );

        $importId = $this->importId;

        Bus::batch($batch)
            ->name("tire-import-{$this->importId}")
            ->finally(function () use ($importId) {
                // Атомарный переход: только один вызов сможет обновить статус
                $updated = ProductImport::where('id', $importId)
                    ->where('status', 'processing')
                    ->update(['status' => 'completed', 'finished_at' => now()]);

                if ($updated) {
                    $import = ProductImport::find($importId);
                    if ($import) {
                        event(new ImportCompleted($import));
                    }
                }

                app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);
            })
            ->dispatch();
    }
}
