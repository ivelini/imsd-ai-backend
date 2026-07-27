<?php

namespace App\DTOs\TireImport;

/** Результат парсинга XLSX-файла. */
final readonly class ParsedImportFileResult
{
    /**
     * @param  string[]  $headerColumns
     * @param  string[]  $chunkFilePaths  Пути к JSON-файлам чанков
     */
    public function __construct(
        public array $headerColumns,
        public array $chunkFilePaths,
        public int $totalRows,
    ) {}
}
