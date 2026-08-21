<?php

namespace Tests\Feature\Import;

use App\DTOs\Catalog\OriginInfo;
use App\Models\Catalog\Origin\ProductOrigin;
use App\Services\Import\OriginResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Резолв product_origin по триплету (vendor, manufacture_country, manufacture_year). */
class OriginResolverTest extends TestCase
{
    use RefreshDatabase;

    private OriginResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(OriginResolver::class);
    }

    public function test_resolve_reuses_same_origin(): void
    {
        $originInfo = new OriginInfo(badge: 'Shandong Haohua Tire', description: null);

        $first = $this->resolver->resolve($originInfo, null, null);
        $second = $this->resolver->resolve($originInfo, null, null);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ProductOrigin::count());
    }

    public function test_resolve_null_when_all_missing(): void
    {
        $this->assertNull($this->resolver->resolve(null, null, null));
        $this->assertSame(0, ProductOrigin::count());
    }

    public function test_resolve_partial_triplet(): void
    {
        $origin = $this->resolver->resolve(
            new OriginInfo(badge: 'Shandong Haohua Tire', description: null),
            null,
            null,
        );

        $this->assertNotNull($origin);
        $this->assertSame('Shandong Haohua Tire', $origin->vendor?->badge);
        $this->assertNull($origin->manufacture_country);
        $this->assertNull($origin->manufacture_year);
    }
}
