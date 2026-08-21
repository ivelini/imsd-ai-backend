<?php

namespace App\DTOs\WheelImport;

use App\DTOs\Catalog\OriginInfo;

/** Входные данные для Action UpsertWheelProduct. */
final readonly class UpsertWheelProductInput
{
    /**
     * @param  bool  $descriptionPresent  Колонка description была в файле (ключ есть в данных)
     * @param  bool  $originPresent  Хотя бы одна колонка origin_* была в файле
     */
    public function __construct(
        public string $ean,
        public string $brandName,
        public string $name,
        public ?string $countryName,
        public ?string $color,
        public ?int $diameter,
        public ?string $width,
        public ?string $pcd1,
        public ?string $pcd2,
        public ?string $hubDiameter,
        public ?string $et,
        public ?string $wheelTypeRaw,
        public ?string $description = null,
        public bool $descriptionPresent = false,
        public ?OriginInfo $originVendor = null,
        public ?OriginInfo $originManufactureCountry = null,
        public ?OriginInfo $originManufactureYear = null,
        public bool $originPresent = false,
    ) {}
}
