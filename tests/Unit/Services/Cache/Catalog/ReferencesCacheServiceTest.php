<?php

namespace Tests\Unit\Services\Cache\Catalog;

use App\Services\Cache\Catalog\ReferencesCacheService;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;

/** Кеш справочников: remember/forget на реальной array-реализации кеша. */
class ReferencesCacheServiceTest extends TestCase
{
    public function test_remember_caches_result(): void
    {
        $service = new ReferencesCacheService(new Repository(new ArrayStore), 3600);
        $calls = 0;

        $first = $service->remember(function () use (&$calls) {
            $calls++;

            return ['test'];
        });
        $second = $service->remember(function () use (&$calls) {
            $calls++;

            return ['changed'];
        });

        $this->assertSame(['test'], $first);
        $this->assertSame(['test'], $second);
        $this->assertSame(1, $calls);
    }

    public function test_forget_invalidates_cache(): void
    {
        $service = new ReferencesCacheService(new Repository(new ArrayStore), 3600);
        $calls = 0;

        $service->remember(function () use (&$calls) {
            $calls++;

            return ['test'];
        });
        $service->forget();
        $result = $service->remember(function () use (&$calls) {
            $calls++;

            return ['changed'];
        });

        $this->assertSame(['changed'], $result);
        $this->assertSame(2, $calls);
    }
}
