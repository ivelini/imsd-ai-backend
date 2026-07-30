<?php

namespace App\Observers;

use App\Models\Catalog\Brand\Brand;
use App\Services\Cache\Catalog\ReferencesCacheService;

/** Инвалидация кеша справочников при изменении бренда. */
final readonly class BrandObserver
{
    public function __construct(
        private ReferencesCacheService $referencesCache,
    ) {}

    public function saved(Brand $brand): void
    {
        $this->referencesCache->forget();
    }

    public function deleted(Brand $brand): void
    {
        $this->referencesCache->forget();
    }
}
