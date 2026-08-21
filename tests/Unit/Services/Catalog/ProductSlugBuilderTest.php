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
            'gislaved-soft-frost-200-195-55-r16-91t-studded-runflat',
            ProductSlugBuilder::tire('gislaved', 'soft-frost-200', 195, 55, '16', '91', 'T', true, true),
        );
    }

    public function test_tire_omits_index_when_absent(): void
    {
        $this->assertSame(
            'gislaved-soft-frost-200-195-55-r16',
            ProductSlugBuilder::tire('gislaved', 'soft-frost-200', 195, 55, '16', null, null, false, false),
        );
    }

    public function test_tire_index_parts_combined(): void
    {
        $this->assertSame(
            'gislaved-soft-frost-200-195-55-r16-91',
            ProductSlugBuilder::tire('gislaved', 'soft-frost-200', 195, 55, '16', '91', null, false, false),
        );
        $this->assertSame(
            'gislaved-soft-frost-200-195-55-r16-t',
            ProductSlugBuilder::tire('gislaved', 'soft-frost-200', 195, 55, '16', null, 'T', false, false),
        );
    }

    public function test_tire_omits_null_dimensions(): void
    {
        $this->assertSame(
            'nokian-hakka-r16',
            ProductSlugBuilder::tire('nokian', 'hakka', null, null, '16', null, null, false, false),
        );
    }

    public function test_wheel_full_slug(): void
    {
        $this->assertSame(
            'nokian-xx-7-5-18-45-4x98-58-6',
            ProductSlugBuilder::wheel('nokian', 'XX', '7.5', 18, '45', '4*98', '58.6'),
        );
    }

    public function test_wheel_pcd_and_dot(): void
    {
        $slug = ProductSlugBuilder::wheel('nokian', 'XX', '7', 16, '45', '5*114.3', '58.6');

        $this->assertStringContainsString('5x114-3', $slug);
        $this->assertStringContainsString('58-6', $slug);
        $this->assertStringNotContainsString('.', $slug);
    }
}
