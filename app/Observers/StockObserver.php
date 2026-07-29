<?php

namespace App\Observers;

use App\Models\Catalog\Stock;
use App\Services\Cache\Catalog\ProductCacheService;

/** Инвалидация кеша товара при изменении остатка на складе. */
final readonly class StockObserver
{
    public function __construct(private ProductCacheService $cache) {}

    public function saved(Stock $stock): void
    {
        $this->cache->forgetByTypeAndId($stock->stockable_type, $stock->stockable_id);
    }

    public function deleted(Stock $stock): void
    {
        $this->cache->forgetByTypeAndId($stock->stockable_type, $stock->stockable_id);
    }
}
