<?php

namespace App\DTOs\VehicleImport;

use App\Enums\Vehicle\Position;

/** Один типоразмер шины из CSV-ячейки. */
final readonly class ParsedTireSize
{
    public function __construct(
        public int $width,
        public int $profile,
        public string $diameter,
        public string $type,
        public ?Position $position,
    ) {}
}
