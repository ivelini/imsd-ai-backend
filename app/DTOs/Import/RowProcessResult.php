<?php

namespace App\DTOs\Import;

/** Результат обработки одной строки JSON-чанка импорта. */
final readonly class RowProcessResult
{
    public function __construct(
        public bool $created,
        public ?int $stockId,
    ) {}
}
