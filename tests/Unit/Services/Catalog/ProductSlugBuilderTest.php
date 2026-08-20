<?php

namespace Tests\Unit\Services\Catalog;

use App\Services\Catalog\ProductSlugBuilder;
use PHPUnit\Framework\TestCase;

/** Формулы slug товаров — чистые функции без БД. */
class ProductSlugBuilderTest extends TestCase
{
    public function test_tire_full_slug(): void
    {
        $this->assertSame(
            '215-60-16-studded-runflat',
            ProductSlugBuilder::tire(215, 60, '16', true, true),
        );
    }

    public function test_tire_omits_false_flags(): void
    {
        $this->assertSame(
            '215-60-16',
            ProductSlugBuilder::tire(215, 60, '16', false, false),
        );
    }

    public function test_tire_omits_null_dimensions(): void
    {
        $this->assertSame(
            '16',
            ProductSlugBuilder::tire(null, null, '16', false, false),
        );
    }

    public function test_wheel_full_slug(): void
    {
        $this->assertSame(
            'nokian-xx-7.5-18-45-4x98-58.6',
            ProductSlugBuilder::wheel('nokian', 'XX', '7.5', 18, '45', '4*98', '58.6'),
        );
    }

    public function test_wheel_pcd_and_dot(): void
    {
        $slug = ProductSlugBuilder::wheel('nokian', 'XX', '7', 16, '45', '5*114.3', '58.6');

        $this->assertStringContainsString('5x114.3', $slug);
        $this->assertStringContainsString('58.6', $slug);
    }
}
