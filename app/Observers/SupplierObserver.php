<?php

namespace App\Observers;

use App\Models\Catalog\Supplier\Supplier;
use App\Services\Cache\Catalog\ReferencesCacheService;

/** Инвалидация кеша справочников при изменении поставщика. */
final readonly class SupplierObserver
{
    public function __construct(
        private ReferencesCacheService $referencesCache,
    ) {}

    public function saved(Supplier $supplier): void
    {
        $this->referencesCache->forget();
    }

    public function deleted(Supplier $supplier): void
    {
        $this->referencesCache->forget();
    }
}
