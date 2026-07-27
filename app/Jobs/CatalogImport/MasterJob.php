<?php

namespace App\Jobs\CatalogImport;

use App\Actions\TireImport\ParseImportFile;
use App\DTOs\TireImport\ParseImportFileInput;
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
    ) {}

    public function handle(ParseImportFile $parseAction): void
    {
        $import = ProductImport::findOrFail($this->importId);
        $import->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $chunkDir = storage_path('app/'.trim(config('tire_import.chunk_path'), '/'));
            $input = new ParseImportFileInput(
                filePath: $this->filePath,
                batchId: (string) $this->importId,
                chunkSize: config('tire_import.chunk_size', 500),
                chunkDir: $chunkDir,
            );

            $result = $parseAction->execute($input);

            $import->update([
                'total_rows' => $result->totalRows,
            ]);

            if (empty($result->chunkFilePaths)) {
                $import->update([
                    'status' => 'completed',
                    'finished_at' => now(),
                ]);

                return;
            }

            $batch = [];
            foreach ($result->chunkFilePaths as $chunkPath) {
                $batch[] = new ChunkJob(
                    importId: $this->importId,
                    chunkFilePath: $chunkPath,
                );
            }

            Bus::batch($batch)
                ->name("tire-import-{$this->importId}")
                ->finally(function () use ($import) {
                    $import->refresh();
                    if ($import->status === 'processing') {
                        $import->update([
                            'status' => 'completed',
                            'finished_at' => now(),
                        ]);
                    }
                })
                ->dispatch();

        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }
}
