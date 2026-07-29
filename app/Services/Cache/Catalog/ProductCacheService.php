<?php

namespace App\Services\Cache\Catalog;

use App\Models\Catalog\TireProduct;
use App\Models\Catalog\WheelProduct;
use Illuminate\Cache\Repository;

/** Кеш карточки товара (шина/диск) с остатками на складах. */
final readonly class ProductCacheService
{
    private const KEY_TIRE = 'tire_product_%d';

    private const KEY_WHEEL = 'wheel_product_%d';

    public function __construct(private Repository $cache) {}

    public function rememberTire(int $id, callable $query): TireProduct
    {
        $key = sprintf(self::KEY_TIRE, $id);
        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $product = $query();
        $this->cache->put($key, $product, config('cache_ttl.product'));

        return $product;
    }

    public function rememberWheel(int $id, callable $query): WheelProduct
    {
        $key = sprintf(self::KEY_WHEEL, $id);
        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $product = $query();
        $this->cache->put($key, $product, config('cache_ttl.product'));

        return $product;
    }

    public function forgetTire(int $id): void
    {
        $this->cache->forget(sprintf(self::KEY_TIRE, $id));
    }

    public function forgetWheel(int $id): void
    {
        $this->cache->forget(sprintf(self::KEY_WHEEL, $id));
    }

    /** Инвалидация по типу товара (tire/wheel) и ID. */
    public function forgetByTypeAndId(string $type, int $id): void
    {
        if ($type === TireProduct::class || $type === 'tire') {
            $this->forgetTire($id);
        } elseif ($type === WheelProduct::class || $type === 'wheel') {
            $this->forgetWheel($id);
        }
    }
}
