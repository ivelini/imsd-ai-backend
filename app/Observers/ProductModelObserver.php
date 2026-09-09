<?php

namespace App\Observers;

use App\Models\Catalog\Model\ProductModel;
use App\Services\Cache\Catalog\ReferencesCacheService;

/** Инвалидация кеша справочников при изменении модели товара. */
final readonly class ProductModelObserver
{
    public function __construct(
        private ReferencesCacheService $referencesCache,
    ) {}

    public function saved(ProductModel $model): void
    {
        $this->referencesCache->forget();
    }

    public function deleted(ProductModel $model): void
    {
        $this->referencesCache->forget();
    }
}
