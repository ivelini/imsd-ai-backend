<?php

namespace App\DTOs\VehicleImport;

/** Входные данные для VehicleImportMasterJob. */
final readonly class VehicleImportMasterJobInput
{
    public function __construct(
        public int $importId,
        public string $filePath,
        public int $chunkSize,
        public string $chunkPath,
    ) {}
}
