<?php

namespace App\Services\Import;

use App\DTOs\Import\RowProcessResult;

/** Обработка одной строки JSON-чанка: upsert товара + остаток. */
interface ChunkRowProcessor
{
    /** @param  array<string, mixed>  $rowData */
    public function process(array $rowData): RowProcessResult;
}
