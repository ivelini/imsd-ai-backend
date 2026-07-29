<?php

namespace App\DTOs\TireImport;

/** Входные данные для Action UpsertWheelProduct. */
final readonly class UpsertWheelProductInput
{
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
        public ?string $supplierName,
        public ?string $description,
    ) {}
}
