<?php

namespace App\Services\Cache\Catalog;

use Illuminate\Contracts\Cache\Repository;

/**
 * Кеш публичных фасетов фильтра дисков.
 *
 * Инвалидируется Observer'ами (смена остатков, цен, брендов) и явным forget()
 * после импортов. Аналог TireFilterValuesCacheService.
 */
final readonly class WheelFilterValuesCacheService
{
    /** Индекс ключей вариантов фасетов — forget() сбрасывает все, а не один. */
    private const INDEX_KEY = 'wheel-filter:index';

    public function __construct(
        private Repository $cache,
        private int $ttl,
    ) {}

    /**
     * @param  array<string, mixed>  $filters  Активные фильтры — входят в ключ кеша
     */
    public function remember(callable $query, ?int $cityId = null, array $filters = []): array
    {
        ksort($filters);

        // null — город по умолчанию из конфига, резолвится в замыкании по имени
        $key = 'wheel-filter:'.($cityId ?? 'default').':'.md5(serialize($filters));

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
