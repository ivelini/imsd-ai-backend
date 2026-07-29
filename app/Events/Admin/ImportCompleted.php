<?php

namespace App\Events\Admin;

use App\Models\System\ProductImport;

/** Импорт товаров завершён (успешно или с ошибками). */
class ImportCompleted
{
    public function __construct(
        public readonly ProductImport $import,
    ) {}
}
