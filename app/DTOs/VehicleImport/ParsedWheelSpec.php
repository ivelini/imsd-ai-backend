<?php

namespace App\DTOs\VehicleImport;

use App\Enums\Vehicle\Position;

/** Одна спецификация диска из CSV-ячейки. */
final readonly class ParsedWheelSpec
{
    public function __construct(
        public float $width,
        public int $diameter,
        public float $et,
        public string $pcd,
        public float $hubDiameter,
        public string $bolts,
        public string $type,
        public ?Position $position,
    ) {}
}
