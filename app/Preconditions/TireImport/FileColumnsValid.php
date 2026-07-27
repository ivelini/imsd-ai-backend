<?php

namespace App\Preconditions\TireImport;

use DomainException;

/** Проверка обязательных колонок в заголовке XLSX. */
final readonly class FileColumnsValid
{
    /**
     * @param  string[]  $headerColumns
     */
    public function ensure(array $headerColumns): void
    {
        $required = config('tire_import.required_columns', []);
        $missing = array_diff($required, $headerColumns);

        if (! empty($missing)) {
            throw new DomainException(
                'Отсутствуют обязательные колонки: '.implode(', ', $missing)
            );
        }
    }
}
