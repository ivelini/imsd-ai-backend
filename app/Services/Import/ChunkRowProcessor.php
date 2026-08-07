<?php

namespace App\Services\Import;

/** Обработка одной строки JSON-чанка: upsert товара + остаток. */
interface ChunkRowProcessor
{
    /** @param  array<string, mixed>  $rowData */
    public function process(array $rowData): bool; // true=created, false=updated
}
