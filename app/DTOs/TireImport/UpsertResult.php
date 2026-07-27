<?php

namespace App\DTOs\TireImport;

/** Результат выполнения команды импорта: создано или обновлено. */
final readonly class UpsertResult
{
    public function __construct(
        public bool $created,
    ) {}
}
