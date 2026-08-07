<?php

namespace Tests\Unit\Enums\Catalog;

use App\Enums\Catalog\BrandType;
use App\Enums\Catalog\ProductType;
use PHPUnit\Framework\TestCase;

/** Тип бренда: покрытие категорий товаров и лейблы справочника. */
class BrandTypeTest extends TestCase
{
    public function test_covers_maps_categories(): void
    {
        $this->assertTrue(BrandType::Tire->covers(ProductType::Tire));
        $this->assertFalse(BrandType::Tire->covers(ProductType::Wheel));

        $this->assertFalse(BrandType::Wheel->covers(ProductType::Tire));
        $this->assertTrue(BrandType::Wheel->covers(ProductType::Wheel));

        $this->assertTrue(BrandType::Both->covers(ProductType::Tire));
        $this->assertTrue(BrandType::Both->covers(ProductType::Wheel));
    }

    public function test_labels_match_references(): void
    {
        $this->assertSame('Шинные', BrandType::Tire->label());
        $this->assertSame('Дисковые', BrandType::Wheel->label());
        $this->assertSame('Шины и диски', BrandType::Both->label());
    }
}
