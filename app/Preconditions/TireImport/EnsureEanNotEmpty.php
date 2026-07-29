<?php

namespace App\Preconditions\TireImport;

use DomainException;

/** Проверка: EAN товара не может быть пустым. */
final readonly class EnsureEanNotEmpty
{
    public function ensure(string $ean): void
    {
        if ($ean === '') {
            throw new DomainException('EAN не может быть пустым.');
        }
    }
}
