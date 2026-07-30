<?php

namespace App\Jobs\CatalogImport;

use App\Events\Admin\ImportCompleted;
use App\Models\System\ProductImport;
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

    /** @var array<string, string> */
    private array $columnMap;

    /** @var string[] */
    private array $requiredColumns;

    /**
     * @param  array<string, string>  $columnMap
     * @param  string[]  $requiredColumns
     */
    public function __construct(
        public readonly int $importId,
        public readonly string $filePath,
        array $columnMap,
        array $requiredColumns,
    ) {
        $this->columnMap = $columnMap;
        $this->requiredColumns = $requiredColumns;
    }

    public function handle(ReferenceResolver $resolver): void
    {
        $import = ProductImport::findOrFail($this->importId);
        $this->markProcessing($import);

        try {
            [$total, $created, $updated, $errors] = $this->processFile($resolver);

            $this->markCompleted($import, $total, $created, $updated, $errors);
        } catch (\Throwable $e) {
            $this->markFailed($import, $e);
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
                    $headerColumns = $this->extractHeaders($row);
                    $this->ensureRequiredColumns($headerColumns);

                    continue;
                }

                try {
                    $data = $this->rowToAssoc($headerColumns, $row);

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

    /**
     * @return string[]
     */
    private function extractHeaders($row): array
    {
        $headers = [];
        foreach ($row->getCells() as $cell) {
            $headers[] = trim((string) $cell->getValue());
        }

        return $headers;
    }

    /**
     * @param  string[]  $columns
     */
    private function ensureRequiredColumns(array $columns): void
    {
        $missing = array_diff($this->requiredColumns, $columns);

        if (! empty($missing)) {
            throw new \RuntimeException(
                'Отсутствуют обязательные колонки: '.implode(', ', $missing)
            );
        }
    }

    /**
     * @param  string[]  $columns
     * @return array<string, mixed>
     */
    private function rowToAssoc(array $columns, $row): array
    {
        $result = [];
        foreach ($columns as $i => $colName) {
            $cells = $row->getCells();
            $v = $cells[$i]?->getValue();
            $mapped = $this->columnMap[$colName] ?? $colName;
            $result[$mapped] = $v !== null ? (string) $v : null;
        }

        return $result;
    }

    private function markProcessing(ProductImport $import): void
    {
        $import->update(['status' => 'processing', 'started_at' => now()]);
    }

    /**
     * @param  array<int, array{row: int, name: string, error: string}>  $errors
     */
    private function markCompleted(ProductImport $import, int $total, int $created, int $updated, array $errors): void
    {
        $import->update([
            'status' => 'completed',
            'total_rows' => $total,
            'processed_rows' => $total,
            'created_rows' => $created,
            'updated_rows' => $updated,
            'failed_rows' => count($errors),
            'errors' => $errors ?: null,
            'finished_at' => now(),
        ]);

        event(new ImportCompleted($import));
    }

    private function markFailed(ProductImport $import, \Throwable $e): void
    {
        $import->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'finished_at' => now(),
        ]);
        event(new ImportCompleted($import));
    }
}
