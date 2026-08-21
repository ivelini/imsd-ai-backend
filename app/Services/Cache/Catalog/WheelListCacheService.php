<?php

namespace App\Services\Cache\Catalog;

use Illuminate\Contracts\Cache\Repository;

/**
 * Кеш публичного листинга дисков.
 *
 * Инвалидируется Observer'ами и явным forget() после пересчёта catalog_prices
 * (upsert не триггерит Eloquent-события). Аналог TireListCacheService.
 */
final readonly class WheelListCacheService
{
    /** Индекс ключей вариантов листинга — forget() сбрасывает все, а не один. */
    private const INDEX_KEY = 'wheel-list:index';

    public function __construct(
        private Repository $cache,
        private int $ttl,
    ) {}

    /**
     * @param  array<string, mixed>  $filters  Активные фильтры — входят в ключ кеша
     */
    public function remember(
        callable $query,
        ?int $cityId,
        array $filters,
        int $page,
        int $perPage,
        ?string $sortBy,
        string $sortDir,
    ): array {
        ksort($filters);

        // v2: origin — происхождение товара в элементе листинга
        $key = 'wheel-list:v2:'.($cityId ?? 'default').':'.md5(serialize([$filters, $page, $perPage, $sortBy, $sortDir]));

        /** @var array $data */
        $data = $this->cache->remember($key, $this->ttl, $query);

        $this->track($key);

        return $data;
    }

    public function forget(): void
    {
        /** @var list<string> $keys */
        $keys = $this->cache->get(self::INDEX_KEY, []);

        foreach ($keys as $key) {
            $this->cache->forget($key);
        }

        $this->cache->forget(self::INDEX_KEY);
    }

    /** Регистрация ключа в индексе (гонка теряет ключ — вариант устареет по TTL, приемлемо). */
    private function track(string $key): void
    {
        /** @var list<string> $keys */
        $keys = $this->cache->get(self::INDEX_KEY, []);

        if (in_array($key, $keys, true)) {
            return;
        }

        $keys[] = $key;
        $this->cache->put(self::INDEX_KEY, $keys, $this->ttl);
    }
}
