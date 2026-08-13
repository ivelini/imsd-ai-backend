<?php

namespace App\Jobs\GeoImport;

use App\Actions\Catalog\PopulateCatalogPrices;
use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\Models\Delivery\City;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\DeliveryPoint;
use App\Models\Delivery\Region;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;
use App\Services\Import\ColumnDetector;
use App\Services\Import\ImportStatusUpdater;
use App\Services\Import\RowAssembler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;
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
        private readonly RowAssembler $rowAssembler = new RowAssembler,
    ) {}

    public function handle(ImportStatusUpdater $statusUpdater): void
    {
        $statusUpdater->markProcessing($this->importId);

        try {
            [$total, $errors] = $this->processFile();

            $statusUpdater->markCompleted($this->importId, [
                'total_rows' => $total,
                'processed_rows' => $total,
                'failed_rows' => count($errors),
                'errors' => $errors ?: null,
            ]);

            app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);
            // upsert() в catalog_prices не триггерит Eloquent-события — инвалидация вручную
            app(TireFilterValuesCacheService::class)->forget();
        } catch (\Throwable $e) {
            $statusUpdater->markFailed($this->importId, $e);
            throw $e;
        }
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
                $headerColumns = $this->rowAssembler->extractHeaders($row);
                $this->rowAssembler->ensureRequiredColumns($headerColumns, $this->requiredColumns);
                $this->priceColumns = ColumnDetector::detectPriceColumns($headerColumns);

                continue;
            }

            try {
                $data = $this->rowAssembler->toAssoc($headerColumns, $row, $this->columnMap);
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
            [
                'name' => $data['city_name'],
                'slug' => Str::slug($data['city_name']),
            ],
        );

        // Города, созданные до появления slug, заполняем при следующем импорте
        if ($city->slug === null) {
            $city->update(['slug' => Str::slug($city->name)]);
        }

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
}
