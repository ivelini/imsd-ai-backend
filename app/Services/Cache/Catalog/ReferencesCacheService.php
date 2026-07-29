<?php

namespace App\Services\Cache\Catalog;

use Illuminate\Contracts\Cache\Repository;

/**
 * Кеш справочников для дропдаунов (бренды, поставщики, страны, enum-ы).
 *
 * Инвалидируется Observer'ами BrandObserver и SupplierObserver.
 */
final readonly class ReferencesCacheService
{
    private const KEY = 'references';

    public function __construct(
        private Repository $cache,
    ) {}

    public function remember(callable $query): array
    {
        $cached = $this->cache->get(self::KEY);

        if ($cached !== null) {
            return $cached;
        }

        $data = $query();
        $this->cache->put(self::KEY, $data, config('cache_ttl.references'));

        return $data;
    }

    public function forget(): void
    {
        $this->cache->forget(self::KEY);
    }
}
