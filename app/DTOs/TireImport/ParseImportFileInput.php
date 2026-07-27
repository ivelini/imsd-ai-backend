<?php

namespace App\DTOs\TireImport;

/** Входные данные для Action ParseImportFile. */
final readonly class ParseImportFileInput
{
    /**
     * @param  string[]  $requiredColumns  Обязательные колонки XLSX.
     * @param  array<string, string>  $columnMap  Маппинг XLSX-колонок → поля данных.
     */
    public function __construct(
        public string $filePath,
        public string $batchId,
        public int $chunkSize,
        public string $chunkDir,
        public array $requiredColumns = [],
        public array $columnMap = [],
    ) {}
}
