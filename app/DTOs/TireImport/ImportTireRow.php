<?php

namespace App\DTOs\TireImport;

/** Строка из XLSX после маппинга. */
final readonly class ImportTireRow
{
    /**
     * @param  array<string, string|null>  $descriptions  Ключи: vendor, default, manufacture_country, manufacture_year, euro_label
     * @param  array<string, string|null>  $promos  promo_1 … promo_5
     */
    public function __construct(
        public string $ean,
        public string $brand_name,
        public string $season_raw,
        public ?string $country_name,
        public string $name,
        public ?int $width,
        public ?int $profile,
        public ?string $diameter,
        public ?string $load_speed_index,
        public ?string $is_runflat_raw,
        public ?string $is_studded_raw,
        public ?string $supplier_name,
        public ?string $warehouse_name,
        public ?int $quantity,
        public ?float $purchase_price,
        public ?float $minimum_market_price,
        public array $descriptions,
        public array $promos,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            ean: trim((string) ($data['ean'] ?? '')),
            brand_name: trim((string) ($data['brand_name'] ?? '')),
            season_raw: trim((string) ($data['season_raw'] ?? '')),
            country_name: isset($data['country_name']) ? trim((string) $data['country_name']) : null,
            name: trim((string) ($data['name'] ?? '')),
            width: isset($data['width']) ? (int) $data['width'] : null,
            profile: isset($data['profile']) ? (int) $data['profile'] : null,
            diameter: isset($data['diameter']) ? trim((string) $data['diameter']) : null,
            load_speed_index: isset($data['load_speed_index']) ? trim((string) $data['load_speed_index']) : null,
            is_runflat_raw: isset($data['is_runflat_raw']) ? trim((string) $data['is_runflat_raw']) : null,
            is_studded_raw: isset($data['is_studded_raw']) ? trim((string) $data['is_studded_raw']) : null,
            supplier_name: isset($data['supplier_name']) ? trim((string) $data['supplier_name']) : null,
            warehouse_name: isset($data['warehouse_name']) ? trim((string) $data['warehouse_name']) : null,
            quantity: isset($data['quantity']) ? (int) $data['quantity'] : null,
            purchase_price: isset($data['purchase_price']) ? (float) $data['purchase_price'] : null,
            minimum_market_price: isset($data['minimum_market_price']) ? (float) $data['minimum_market_price'] : null,
            descriptions: $data['descriptions'] ?? [],
            promos: $data['promos'] ?? [],
        );
    }
}
