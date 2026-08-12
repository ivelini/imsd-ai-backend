<?php

namespace App\Services\Import;

use OpenSpout\Common\Entity\Row;

/** Сборка ассоциативного массива из строки XLSX: заголовки → значения. */
final readonly class RowAssembler
{
    /** @return string[] */
    public function extractHeaders(Row $row): array
    {
        $headers = [];
        foreach ($row->getCells() as $cell) {
            $headers[] = trim((string) $cell->getValue());
        }

        return $headers;
    }

    /**
     * @param  string[]  $columns
     * @param  string[]  $required
     *
     * @throws \RuntimeException
     */
    public function ensureRequiredColumns(array $columns, array $required): void
    {
        $missing = array_diff($required, $columns);

        if (! empty($missing)) {
            throw new \RuntimeException(
                'Отсутствуют обязательные колонки: '.implode(', ', $missing)
            );
        }
    }

    /**
     * Преобразует строку XLSX в ассоциативный массив [ключ_колонки => значение].
     *
     * @param  string[]  $columns  Заголовки колонок
     * @param  string[]  $columnMap  Маппинг заголовок → внутренний ключ
     * @return array<string, string|null>
     */
    public function toAssoc(array $columns, Row $row, array $columnMap): array
    {
        $cells = $row->getCells();
        $result = [];

        foreach ($columns as $i => $colName) {
            $v = ($cells[$i] ?? null)?->getValue();
            $mapped = $columnMap[$colName] ?? $colName;
            $result[$mapped] = $v !== null ? (string) $v : null;
        }

        return $result;
    }
}
