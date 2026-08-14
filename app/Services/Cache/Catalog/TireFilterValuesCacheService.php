<?php

namespace App\Services\Cache\Catalog;

use Illuminate\Contracts\Cache\Repository;

/**
 * Кеш фасетов фильтра шин.
 *
 * Инвалидируется Observer'ами и явным forget() после пересчёта catalog_prices
 * (upsert не триггерит Eloquent-события).
 */
final readonly class TireFilterValuesCacheService
{
    private string $key;

    public function __construct(
        private Repository $cache,
        private int $ttl,
        string $defaultCityName,
    ) {
        $this->key = "tire-filter:{$defaultCityName}";
    }

    /**
     * @param  array<string, mixed>  $filters  Активные фильтры — входят в ключ кеша
     */
    public function remember(callable $query, array $filters = []): array
    {
        ksort($filters);

        /** @var array $data */
        $data = $this->cache->remember($this->key.':'.md5(serialize($filters)), $this->ttl, $query);

        return $data;
    }

    public function forget(): void
    {
        $this->cache->forget($this->key);
    }
}
