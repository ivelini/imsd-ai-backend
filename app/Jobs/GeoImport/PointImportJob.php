<?php

namespace App\Jobs\GeoImport;

use App\Models\Delivery\City;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\DeliveryPoint;
use App\Models\Delivery\DeliveryPointCoefficient;
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

    /** @var array<string, array{int, int}> Маппинг колонок цены: имя_колонки → [price_from, price_to] */
    private const PRICE_RANGES = [
        'price_0_5000' => [0, 5000],
        'price_5001_8500' => [5001, 8500],
        'price_8501_10000' => [8501, 10000],
        'price_10001_15000' => [10001, 15000],
        'price_15001_100000' => [15001, 100000],
    ];

    /** @var array<string, null> */
    private const COEFF_MAP = [
        'coeff_31_60' => null,
        'coeff_61_100' => null,
    ];

    /** @var string[] */
    private array $booleanTrue;

    public function __construct(
        public readonly int $importId,
        public readonly string $filePath,
    ) {
        $this->booleanTrue = config('point_import.boolean_true', ['да', 'yes', 'true']);
    }

    public function handle(): void
    {
        $import = ProductImport::findOrFail($this->importId);
        $import->update(['status' => 'processing', 'started_at' => now()]);

        $total = 0;
        $errors = [];

        try {
            $reader = new Reader;
            $reader->open($this->filePath);

            foreach ($reader->getSheetIterator() as $sheet) {
                $headerColumns = [];
                $columnMap = config('point_import.column_map', []);

                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    if ($rowIndex === 1) {
                        foreach ($row->getCells() as $cell) {
                            $headerColumns[] = trim((string) $cell->getValue());
                        }
                        $this->ensureRequiredColumns($headerColumns);

                        continue;
                    }

                    $data = [];
                    try {
                        $data = $this->rowToAssoc($headerColumns, $row, $columnMap);
                        $this->importRow($data);
                        $total++;
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'row' => $rowIndex,
                            'city' => $data['city_name'] ?? 'N/A',
                            'error' => $e->getMessage(),
                        ];
                        if (count($errors) > 50) {
                            break;
                        }
                    }
                }
            }

            $reader->close();

            $import->update([
                'status' => 'completed',
                'total_rows' => $total,
                'processed_rows' => $total,
                'failed_rows' => count($errors),
                'errors' => $errors ?: null,
                'finished_at' => now(),
            ]);

        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);
            throw $e;
        }
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

        // Наценки по диапазонам цен
        foreach (self::PRICE_RANGES as $col => [$from, $to]) {
            $value = $data[$col] ?? null;
            if ($value !== null && $value !== '') {
                CityPriceRule::updateOrCreate(
                    [
                        'city_id' => $city->id,
                        'price_from' => $from,
                        'price_to' => $to,
                    ],
                    ['markup' => (float) $value],
                );
            }
        }

        // Срок доставки
        if (! empty($data['delivery_days'])) {
            CityDeliveryTime::updateOrCreate(
                ['city_id' => $city->id],
                ['delivery_days' => (int) $data['delivery_days']],
            );
        }

        // Коэффициенты доставки
        foreach (self::COEFF_MAP as $col => $productType) {
            $value = $data[$col] ?? null;
            if ($value !== null && $value !== '') {
                DeliveryPointCoefficient::updateOrCreate(
                    [
                        'price_from' => $this->parseCoeffRange($col)[0],
                        'price_to' => $this->parseCoeffRange($col)[1],
                        'product_type' => $productType,
                    ],
                    ['coefficient' => (float) $value],
                );
            }
        }

        // Точка выдачи
        if (! empty($data['address'])) {
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
    }

    /**
     * @return array{int, int}
     */
    private function parseCoeffRange(string $col): array
    {
        return match ($col) {
            'coeff_31_60' => [31, 60],
            'coeff_61_100' => [61, 100],
            default => [0, 0],
        };
    }

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
        $required = config('point_import.required_columns', []);
        $missing = array_diff($required, $columns);

        if (! empty($missing)) {
            throw new \RuntimeException(
                'Отсутствуют обязательные колонки: '.implode(', ', $missing)
            );
        }
    }

    /**
     * @param  string[]  $columns
     * @param  array<string, string>  $columnMap
     * @return array<string, mixed>
     */
    private function rowToAssoc(array $columns, object $row, array $columnMap): array
    {
        $result = [];
        foreach ($columns as $i => $colName) {
            $cells = $row->getCells();
            $v = $cells[$i]?->getValue();
            $mapped = $columnMap[$colName] ?? $colName;
            $result[$mapped] = $v !== null ? (string) $v : null;
        }

        return $result;
    }
}
