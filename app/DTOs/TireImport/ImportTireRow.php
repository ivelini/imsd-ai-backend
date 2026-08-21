<?php

namespace App\DTOs\TireImport;

use App\DTOs\Catalog\OriginInfo;
use App\DTOs\Catalog\Tire\EuroLabel;

/** Строка из XLSX после маппинга. */
final readonly class ImportTireRow
{
    /**
     * @param  array<string, string|null>  $promos  promo_1 … promo_5
     * @param  bool  $description_present  Колонка description была в файле (ключ есть в данных)
     * @param  bool  $origin_present  Хотя бы одна колонка origin_* была в файле
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
        public ?string $warehouse_name,
        public ?int $quantity,
        public ?float $purchase_price,
        public ?float $minimum_market_price,
        public ?EuroLabel $euroLabel,
        public ?string $description,
        public bool $description_present,
        public ?OriginInfo $origin_vendor,
        public ?OriginInfo $origin_manufacture_country,
        public ?OriginInfo $origin_manufacture_year,
        public bool $origin_present,
        public array $promos,
    ) {}
}
