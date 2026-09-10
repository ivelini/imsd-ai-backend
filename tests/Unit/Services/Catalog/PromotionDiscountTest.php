<?php

namespace Tests\Unit\Services\Catalog;

use App\Services\Catalog\PromotionDiscount;
use PHPUnit\Framework\TestCase;

/** Формула скидки по типу акции (чистая функция, ADR 0001). */
class PromotionDiscountTest extends TestCase
{
    public function test_percent_discount(): void
    {
        $this->assertSame(1700.0, PromotionDiscount::apply(2000.0, 'percent', 15.0));
    }

    public function test_fixed_discount(): void
    {
        $this->assertSame(1500.0, PromotionDiscount::apply(2000.0, 'fixed', 500.0));
    }

    public function test_special_sets_price(): void
    {
        // special задаёт цену, а не вычитает сумму
        $this->assertSame(1299.0, PromotionDiscount::apply(2000.0, 'special', 1299.0));
    }

    public function test_fixed_discount_cannot_go_negative(): void
    {
        // Граница: скидка больше цены — цена обнуляется, а не уходит в минус
        $this->assertSame(0.0, PromotionDiscount::apply(2000.0, 'fixed', 5000.0));
    }

    public function test_percent_discount_without_value_changes_nothing(): void
    {
        $this->assertSame(2000.0, PromotionDiscount::apply(2000.0, 'percent', null));
    }
}
