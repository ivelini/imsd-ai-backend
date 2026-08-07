<?php

namespace App\Services\Import;

use App\Enums\Import\ImportType;
use InvalidArgumentException;

/** Фабрика Row-процессоров по типу импорта (tire/wheel). */
final readonly class ChunkRowProcessorFactory
{
    public function __construct(
        private TireRowProcessor $tireProcessor,
        private WheelRowProcessor $wheelProcessor,
        private VehicleRowProcessor $vehicleProcessor,
    ) {}

    public function create(ImportType $type): ChunkRowProcessor
    {
        return match ($type) {
            ImportType::Tire => $this->tireProcessor,
            ImportType::Wheel => $this->wheelProcessor,
            ImportType::Vehicle => $this->vehicleProcessor,
            default => throw new InvalidArgumentException("Импорт {$type->value} не поддерживает чанковую обработку."),
        };
    }
}
