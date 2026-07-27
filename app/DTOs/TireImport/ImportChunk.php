<?php

namespace App\DTOs\TireImport;

/** Чанк строк для обработки одним ChunkJob. */
final readonly class ImportChunk
{
    /**
     * @param  ImportTireRow[]  $rows
     */
    public function __construct(
        public string $batchId,
        public array $rows,
    ) {}
}
