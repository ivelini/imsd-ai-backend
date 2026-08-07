<?php

namespace App\Jobs\VehicleImport;

use App\DTOs\VehicleImport\VehicleImportMasterJobInput;
use App\Enums\Import\ImportType;
use App\Jobs\CatalogImport\ChunkJob;
use App\Models\System\ProductImport;
use App\Services\Import\ImportStatusUpdater;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use RuntimeException;

/** Оркестратор импорта Vehicle: CSV → JSON-чанки → Bus::batch(ChunkJob[]). */
final class VehicleImportMasterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        private readonly VehicleImportMasterJobInput $input,
    ) {}

    public function handle(ImportStatusUpdater $statusUpdater): void
    {
        $statusUpdater->markProcessing($this->input->importId);

        try {
            $chunkFiles = $this->parseAndChunk();

            $totalRows = $this->countRows();
            ProductImport::where('id', $this->input->importId)
                ->update(['total_rows' => $totalRows]);

            if (empty($chunkFiles)) {
                $statusUpdater->markCompleted($this->input->importId);

                return;
            }

            $this->dispatchBatch($chunkFiles, $statusUpdater);
        } catch (\Throwable $e) {
            $statusUpdater->markFailed($this->input->importId, $e);
            throw $e;
        }
    }

    /**
     * @return string[] paths to JSON chunk files
     */
    private function parseAndChunk(): array
    {
        $handle = fopen($this->input->filePath, 'r');
        if (! $handle) {
            throw new RuntimeException('Не удалось открыть CSV-файл.');
        }

        $chunkDir = storage_path('app/'.trim($this->input->chunkPath, '/'));
        if (! is_dir($chunkDir)) {
            mkdir($chunkDir, 0755, true);
        }

        $chunkFiles = [];
        $chunk = [];
        $rowIndex = 0;
        $chunkIndex = 0;
        $delimiter = config('vehicle_import.delimiter', ';');
        $expectedColumns = config('vehicle_import.expected_columns', 14);

        while (($cols = fgetcsv($handle, 4096, $delimiter)) !== false) {
            $rowIndex++;

            if (count($cols) < $expectedColumns) {
                // Дополняем недостающие колонки пустыми строками
                $cols = array_pad($cols, $expectedColumns, '');
            }

            // Обрезаем до ожидаемого количества (на случай лишних колонок)
            $cols = array_slice($cols, 0, $expectedColumns);

            $chunk[] = array_map('trim', $cols);

            if (count($chunk) >= $this->input->chunkSize) {
                $chunkIndex++;
                $chunkFiles[] = $this->writeChunk($chunkDir, $chunkIndex, $chunk);
                $chunk = [];
            }
        }

        fclose($handle);

        if (! empty($chunk)) {
            $chunkIndex++;
            $chunkFiles[] = $this->writeChunk($chunkDir, $chunkIndex, $chunk);
        }

        return $chunkFiles;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function writeChunk(string $chunkDir, int $chunkIndex, array $rows): string
    {
        $path = $chunkDir.'/chunk_'.str_pad((string) $chunkIndex, 4, '0', STR_PAD_LEFT).'.json';

        file_put_contents(
            $path,
            json_encode(['rows' => $rows], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );

        return $path;
    }

    private function countRows(): int
    {
        $handle = fopen($this->input->filePath, 'r');
        if (! $handle) {
            return 0;
        }

        $count = 0;
        while (fgetcsv($handle, 4096, config('vehicle_import.delimiter', ';')) !== false) {
            $count++;
        }
        fclose($handle);

        return $count;
    }

    /**
     * @param  string[]  $chunkFiles
     */
    private function dispatchBatch(array $chunkFiles, ImportStatusUpdater $statusUpdater): void
    {
        $importId = $this->input->importId;

        $batch = array_map(
            fn (string $chunkPath) => new ChunkJob(
                importId: $importId,
                chunkFilePath: $chunkPath,
                importType: ImportType::Vehicle,
            ),
            $chunkFiles,
        );

        Bus::batch($batch)
            ->name("vehicle-import-{$importId}")
            ->finally(static function () use ($importId, $statusUpdater) {
                $statusUpdater->completeIfProcessing($importId);
            })
            ->dispatch();
    }
}
