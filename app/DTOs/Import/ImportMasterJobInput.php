<?php

namespace App\DTOs\Import;

use App\Enums\Import\ImportType;

/** Параметры запуска ImportMasterJob. */
final readonly class ImportMasterJobInput
{
    /**
     * @param  string[]  $requiredColumns
     * @param  array<string, string>  $columnMap
     */
    public function __construct(
        public int $importId,
        public string $filePath,
        public int $chunkSize,
        public string $chunkPath,
        public ImportType $importType,
        public array $requiredColumns = [],
        public array $columnMap = [],
    ) {}
}
