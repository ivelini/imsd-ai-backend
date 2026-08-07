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
        private int $ttl,
    ) {}

    public function remember(callable $query): array
    {
        /** @var array $data */
        $data = $this->cache->remember(self::KEY, $this->ttl, $query);

        return $data;
    }

    public function forget(): void
    {
        $this->cache->forget(self::KEY);
    }
}
