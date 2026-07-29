<?php

namespace App\Jobs\GeoImport;

use App\Actions\Catalog\PopulateCatalogPrices;
use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\Events\Admin\ImportCompleted;
use App\Models\Delivery\City;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\DeliveryPoint;
use App\Models\Delivery\Region;
use App\Models\System\ProductImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use OpenSpout\Reader\XLSX\Reader;

/** Импорт точек выдачи, регионов, городов, наценок и доставки из XLSX. */
final class PointImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 300;

    public int $tries = 2;

    public array $backoff = [10];

    /** @var string[] */
    private array $priceColumns = [];

    /**
     * @param  string[]  $columnMap  Маппинг заголовков XLSX → ключи данных
     * @param  string[]  $requiredColumns  Обязательные колонки (отсутствие → исключение)
     * @param  string[]  $booleanTrue  Значения, считающиеся true для булевых полей
     */
    public function __construct(
        public readonly int $importId,
        public readonly string $filePath,
        private readonly array $columnMap,
        private readonly array $requiredColumns,
        private readonly array $booleanTrue,
    ) {}

    /**
     * @param  string[]  $headers
     * @return string[]
     */
    public static function detectColumnsFromHeaders(array $headers): array
    {
        $price = [];

        foreach ($headers as $header) {
            if (preg_match('/^(\d+)-(\d+)$/u', $header)) {
                $price[] = $header;
            }
        }

        return $price;
    }

    public function handle(): void
    {
        $import = ProductImport::findOrFail($this->importId);
        $this->markImportProcessing($import);

        try {
            [$total, $errors] = $this->processFile();

            $this->markImportCompleted($import, $total, $errors);
        } catch (\Throwable $e) {
            $this->markImportFailed($import, $e);
            throw $e;
        }
    }

    private function markImportProcessing(ProductImport $import): void
    {
        $import->update(['status' => 'processing', 'started_at' => now()]);
    }

    /**
     * @param  array<int, array{row: int, city: string, error: string}>  $errors
     */
    private function markImportCompleted(ProductImport $import, int $total, array $errors): void
    {
        $import->update([
            'status' => 'completed',
            'total_rows' => $total,
            'processed_rows' => $total,
            'failed_rows' => count($errors),
            'errors' => $errors ?: null,
            'finished_at' => now(),
        ]);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput($import->id));

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

    /**
     * @return array{int, array<int, array{row: int, city: string, error: string}>}
     */
    private function processFile(): array
    {
        $reader = new Reader;
        $reader->open($this->filePath);

        $total = 0;
        $errors = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            [$sheetTotal, $sheetErrors] = $this->processSheet($sheet);
            $total += $sheetTotal;
            $errors = [...$errors, ...$sheetErrors];

            // Прерываем при критическом числе ошибок — файл вероятно не того формата
            if (count($errors) > 50) {
                break;
            }
        }

        $reader->close();

        return [$total, $errors];
    }

    /**
     * @return array{int, array<int, array{row: int, city: string, error: string}>}
     */
    private function processSheet($sheet): array
    {
        $total = 0;
        $errors = [];
        $headerColumns = [];

        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            if ($rowIndex === 1) {
                $headerColumns = $this->extractHeaders($row);
                $this->ensureRequiredColumns($headerColumns);
                $this->detectDynamicColumns($headerColumns);

                continue;
            }

            try {
                $data = $this->rowToAssoc($headerColumns, $row);
                $this->importRow($data);
                $total++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'row' => $rowIndex,
                    'city' => $data['city_name'] ?? 'N/A',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [$total, $errors];
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
     * @param  string[]  $headers
     */
    private function detectDynamicColumns(array $headers): void
    {
        $this->priceColumns = self::detectColumnsFromHeaders($headers);
    }

    private function importRow(array $data): void
    {
        $region = Region::firstOrCreate(
            ['code' => (string) $data['region_code']],
            ['name' => $data['region_name']],
        );

        $city = City::firstOrCreate(
            [
                'region_id' => $region->id,
                'name' => $data['city_name'],
            ],
            ['name' => $data['city_name']],
        );

        $this->importPriceRules($city, $data);
        $this->importDeliveryTime($city, $data);
        $this->importDeliveryPoint($city, $data);
    }

    private function importPriceRules(City $city, array $data): void
    {
        foreach ($this->priceColumns as $header) {
            $value = $data[$header] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            preg_match('/^(\d+)-(\d+)$/u', $header, $m);
            CityPriceRule::updateOrCreate(
                [
                    'city_id' => $city->id,
                    'price_from' => (int) $m[1],
                    'price_to' => (int) $m[2],
                ],
                ['markup' => (float) $value],
            );
        }
    }

    private function importDeliveryTime(City $city, array $data): void
    {
        if (empty($data['delivery_days'])) {
            return;
        }

        CityDeliveryTime::updateOrCreate(
            ['city_id' => $city->id],
            ['delivery_days' => (int) $data['delivery_days']],
        );
    }

    private function importDeliveryPoint(City $city, array $data): void
    {
        if (empty($data['address'])) {
            return;
        }

        // Часы работы и выходного дня хранятся в разных колонках, склеиваем в одно поле
        $workHours = trim(($data['work_hours'] ?? '').' '.($data['weekend_hours'] ?? ''));

        DeliveryPoint::updateOrCreate(
            [
                'city_id' => $city->id,
                'address' => $data['address'],
            ],
            [
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'work_hours' => $workHours ?: null,
                'info' => $data['info'] ?? null,
                'pickup_from_truck' => $this->toBool($data['pickup_from_truck_raw'] ?? null),
            ],
        );
    }

    /** Приводит сырое значение из XLSX к bool по белому списку из конфига. */
    private function toBool(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return in_array(mb_strtolower(trim($value)), $this->booleanTrue, true);
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
    private function rowToAssoc(array $columns, object $row): array
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
}
