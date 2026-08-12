<?php

namespace App\Actions\TireImport;

use App\DTOs\TireImport\ParsedImportFileResult;
use App\DTOs\TireImport\ParseImportFileInput;
use App\Services\Import\RowAssembler;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Reader\XLSX\Sheet;

/** Чтение XLSX, валидация колонок, нарезка на JSON-чанки. */
final readonly class ParseImportFile
{
    public function __construct(
        private RowAssembler $rowAssembler = new RowAssembler,
    ) {}

    public function execute(ParseImportFileInput $input): ParsedImportFileResult
    {
        $reader = new Reader;
        $reader->open($input->filePath);

        $sheet = $this->firstSheet($reader);

        $headerColumns = [];
        $totalRows = 0;
        $chunkFilePaths = [];
        $buffer = [];
        $chunkIndex = 0;
        $columnMap = $input->columnMap;
        $requiredColumns = $input->requiredColumns;
        $headerRead = false;

        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            if (! $headerRead) {
                foreach ($row->getCells() as $cell) {
                    $headerColumns[] = trim((string) $cell->getValue());
                }
                $this->rowAssembler->ensureRequiredColumns($headerColumns, $requiredColumns);
                $headerRead = true;

                continue;
            }

            $data = $this->rowAssembler->toAssoc($headerColumns, $row, $columnMap);
            $buffer[] = $data;
            $totalRows++;

            if (count($buffer) >= $input->chunkSize) {
                $chunkFilePaths[] = $this->writeChunk($buffer, $input, $chunkIndex++);
                $buffer = [];
            }
        }

        if (! $headerRead) {
            $reader->close();
            throw new \RuntimeException('XLSX не содержит строк.');
        }

        if (! empty($buffer)) {
            $chunkFilePaths[] = $this->writeChunk($buffer, $input, $chunkIndex++);
        }

        $reader->close();

        return new ParsedImportFileResult(
            headerColumns: $headerColumns,
            chunkFilePaths: $chunkFilePaths,
            totalRows: $totalRows,
        );
    }

    private function firstSheet(Reader $reader): Sheet
    {
        foreach ($reader->getSheetIterator() as $sheet) {
            return $sheet;
        }

        throw new \RuntimeException('XLSX не содержит листов.');
    }

    /**
     * @param  array<int, array<string, string|null>>  $buffer
     */
    private function writeChunk(array $buffer, ParseImportFileInput $input, int $chunkIndex): string
    {
        $dir = $input->chunkDir;

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.'/chunk_'.str_pad((string) $chunkIndex, 4, '0', STR_PAD_LEFT).'.json';

        file_put_contents($path, json_encode([
            'batch_id' => $input->batchId,
            'rows' => $buffer,
        ], JSON_UNESCAPED_UNICODE));

        return $path;
    }
}
