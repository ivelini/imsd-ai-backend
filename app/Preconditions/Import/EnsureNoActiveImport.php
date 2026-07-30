<?php

namespace App\Preconditions\Import;

use App\Enums\Import\ImportType;
use App\Models\System\ProductImport;
use DomainException;

/** Проверка: нет активного импорта того же типа перед запуском нового. */
final readonly class EnsureNoActiveImport
{
    public function ensure(ImportType $type): void
    {
        $exists = ProductImport::where('type', $type->value)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($exists) {
            throw new DomainException(
                'Импорт уже запущен. Дождитесь завершения текущего импорта перед запуском нового.'
            );
        }
    }
}
