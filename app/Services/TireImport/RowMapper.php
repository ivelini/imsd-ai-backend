<?php

namespace App\Services\TireImport;

use App\DTOs\TireImport\ImportTireRow;

/** Маппинг сырой строки XLSX → DTO. */
final class RowMapper
{
    /** @var string[] */
    private array $booleanTrue;

    /** @var array<string, string> */
    private array $seasonMap;

    /**
     * @param  string[]  $booleanTrue
     * @param  array<string, string>  $seasonMap
     */
    public function __construct(
        ?array $booleanTrue = null,
        ?array $seasonMap = null,
    ) {
        $this->booleanTrue = $booleanTrue ?? ['да', 'yes', 'true', '1', '+', 'есть'];
        $this->seasonMap = $seasonMap ?? [
            'зимняя' => 'winter',
            'зимние' => 'winter',
            'зима' => 'winter',
            'зимняя шипованная' => 'winter',
            'зимняя нешипованная' => 'winter',
            'летняя' => 'summer',
            'летние' => 'summer',
            'лето' => 'summer',
            'всесезон' => 'all-season',
            'всесезонная' => 'all-season',
            'всесезонные' => 'all-season',
        ];
    }

    /**
     * @param  array<string, string|null>  $data
     */
    public function map(array $data): ImportTireRow
    {
        $descriptions = [
            'vendor' => $data['vendor_description'] ?? $data['description_vendor'] ?? null,
            'default' => $data['description_default'] ?? null,
            'manufacture_country' => $data['description_manufacture_country'] ?? null,
            'manufacture_year' => $data['description_manufacture_year'] ?? null,
            'euro_label' => $data['description_euro_label'] ?? null,
        ];

        $promos = [];
        for ($i = 1; $i <= 5; $i++) {
            $key = "promo_{$i}";
            $promos[$key] = $data[$key] ?? null;
        }

        return new ImportTireRow(
            ean: trim($data['ean'] ?? $data['product_article'] ?? ''),
            brand_name: trim($data['brand_name'] ?? $data['vendor'] ?? ''),
            season_raw: trim($data['season_raw'] ?? $data['season'] ?? ''),
            country_name: $this->nullableString($data['country_name'] ?? $data['country'] ?? null),
            name: trim($data['name'] ?? ''),
            width: $this->nullableInt($data['width'] ?? null),
            profile: $this->nullableInt($data['profile'] ?? $data['height'] ?? null),
            diameter: $this->nullableString($data['diameter'] ?? null),
            load_speed_index: $this->nullableString($data['load_speed_index'] ?? null),
            is_runflat_raw: $this->nullableString($data['is_runflat_raw'] ?? $data['is_runflat'] ?? null),
            is_studded_raw: $this->nullableString($data['is_studded_raw'] ?? $data['is_spike'] ?? null),
            supplier_name: $this->nullableString($data['supplier_name'] ?? $data['supplier'] ?? null),
            warehouse_name: $this->nullableString($data['warehouse_name'] ?? $data['stock'] ?? null),
            quantity: $this->nullableInt($data['quantity'] ?? $data['count'] ?? null),
            purchase_price: $this->nullableFloat($data['purchase_price'] ?? $data['price'] ?? null),
            minimum_market_price: $this->nullableFloat($data['minimum_market_price'] ?? null),
            descriptions: $descriptions,
            promos: $promos,
        );
    }

    public function toBool(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return in_array(mb_strtolower(trim($value)), $this->booleanTrue, true);
    }

    public function toSeason(?string $value): string
    {
        if ($value === null) {
            return 'summer';
        }

        $key = mb_strtolower(trim($value));

        return $this->seasonMap[$key] ?? 'summer';
    }

    /**
     * Парсинг "86T" → [load: 86, speed: T].
     *
     * @return array{load: string|null, speed: string|null}
     */
    public function parseLoadSpeedIndex(?string $value): array
    {
        if ($value === null || $value === '') {
            return ['load' => null, 'speed' => null];
        }

        $value = trim($value);
        preg_match('/^(\d+)([A-Za-z]?)$/', $value, $m);

        return [
            'load' => isset($m[1]) ? $m[1] : null,
            'speed' => (isset($m[2]) && $m[2] !== '') ? strtoupper($m[2]) : null,
        ];
    }

    public function nullableString(?string $value): ?string
    {
        $v = trim($value ?? '');

        return $v === '' ? null : $v;
    }

    public function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return (int) $value;
    }

    public function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return (float) str_replace([' ', ','], ['', '.'], (string) $value);
    }
}
