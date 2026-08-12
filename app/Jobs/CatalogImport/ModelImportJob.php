<?php

namespace App\Jobs\CatalogImport;

use App\Services\Import\ImportStatusUpdater;
use App\Services\Import\RowAssembler;
use App\Services\TireImport\ReferenceResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use OpenSpout\Reader\XLSX\Reader;

/** Импорт моделей товаров из XLSX. */
final class ModelImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 300;

    public int $tries = 2;

    public array $backoff = [10];

    /** @var string[] */
    private readonly array $requiredColumns;

    /**
     * @param  array<string, string>  $columnMap
     * @param  string[]  $requiredColumns
     */
    public function __construct(
        public readonly int $importId,
        public readonly string $filePath,
        private readonly array $columnMap,
        array $requiredColumns,
        private readonly RowAssembler $rowAssembler = new RowAssembler,
    ) {
        $this->requiredColumns = $requiredColumns;
    }

    public function handle(ReferenceResolver $resolver, ImportStatusUpdater $statusUpdater): void
    {
        $statusUpdater->markProcessing($this->importId);

        try {
            [$total, $created, $updated, $errors] = $this->processFile($resolver);

            $statusUpdater->markCompleted($this->importId, [
                'total_rows' => $total,
                'processed_rows' => $total,
                'created_rows' => $created,
                'updated_rows' => $updated,
                'failed_rows' => count($errors),
                'errors' => $errors ?: null,
            ]);
        } catch (\Throwable $e) {
            $statusUpdater->markFailed($this->importId, $e);
            throw $e;
        }
    }

    /**
     * @return array{int, int, int, array<int, array{row: int, name: string, error: string}>}
     */
    private function processFile(ReferenceResolver $resolver): array
    {
        $reader = new Reader;
        $reader->open($this->filePath);

        $total = 0;
        $created = 0;
        $updated = 0;
        $errors = [];
        $headerColumns = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                if ($rowIndex === 1) {
                    $headerColumns = $this->rowAssembler->extractHeaders($row);
                    $this->rowAssembler->ensureRequiredColumns($headerColumns, $this->requiredColumns);

                    continue;
                }

                try {
                    $data = $this->rowAssembler->toAssoc($headerColumns, $row, $this->columnMap);

                    $brand = $resolver->resolveBrand($data['brand_name'] ?? '');
                    $modelName = $data['name'] ?? '';
                    $type = $data['type'] ?? 'tire';

                    // Для дисков — парсим чистое имя модели из полного названия
                    if ($type === 'wheel') {
                        $modelName = ReferenceResolver::parseWheelModelName($modelName);
                    }

                    $description = $data['description'] ?? null;

                    $exists = $brand->models()->where('name', $modelName)->exists();

                    $model = $resolver->resolveModel($brand, $modelName, $type);

                    if ($description !== null && $description !== '') {
                        $model->update(['description' => $description]);
                    }

                    if ($exists) {
                        $updated++;
                    } else {
                        $created++;
                    }

                    $total++;
                } catch (\Throwable $e) {
                    $errors[] = [
                        'row' => $rowIndex,
                        'name' => $data['name'] ?? 'N/A',
                        'error' => $e->getMessage(),
                    ];

                    if (count($errors) > 50) {
                        break 2;
                    }
                }
            }
        }

        $reader->close();

        return [$total, $created, $updated, $errors];
    }
}
