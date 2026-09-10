<?php

namespace Tests\Unit\Services\Catalog;

use App\Services\Catalog\PromotionMatcher;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/** Выбор акции для товара: приоритет привязки и максимальная скидка (FR ADM-7.1.5, чистая функция). */
class PromotionMatcherTest extends TestCase
{
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = CarbonImmutable::parse('2026-09-10 12:00:00');
    }

    public function test_product_promotion_wins_over_brand_and_catalog(): void
    {
        $matched = PromotionMatcher::match(
            basePrice: 2000.0,
            promotions: [
                $this->promotion(type: 'percent', value: 5.0),
                $this->promotion(type: 'percent', value: 10.0, promotableType: 'brand', promotableId: 7),
                $this->promotion(type: 'percent', value: 7.0, promotableType: 'tire', promotableId: 42),
            ],
            productType: 'tire',
            productId: 42,
            brandId: 7,
            now: $this->now,
        );

        $this->assertSame(7.0, $matched['value']);
    }

    public function test_larger_discount_wins_within_same_scope(): void
    {
        // percent 10 даёт 200 ₽, fixed 500 даёт 500 ₽ — побеждает вторая
        $matched = PromotionMatcher::match(
            basePrice: 2000.0,
            promotions: [
                $this->promotion(type: 'percent', value: 10.0, promotableType: 'tire', promotableId: 42),
                $this->promotion(type: 'fixed', value: 500.0, promotableType: 'tire', promotableId: 42),
            ],
            productType: 'tire',
            productId: 42,
            brandId: 7,
            now: $this->now,
        );

        $this->assertSame('fixed', $matched['type']);
    }

    public function test_expired_promotion_is_ignored(): void
    {
        $matched = PromotionMatcher::match(
            basePrice: 2000.0,
            promotions: [
                $this->promotion(
                    type: 'percent',
                    value: 20.0,
                    startsAt: '2026-08-01 00:00:00',
                    endsAt: '2026-08-31 00:00:00',
                ),
            ],
            productType: 'tire',
            productId: 42,
            brandId: 7,
            now: $this->now,
        );

        $this->assertNull($matched);
    }

    public function test_promotion_for_other_product_does_not_apply(): void
    {
        $matched = PromotionMatcher::match(
            basePrice: 2000.0,
            promotions: [
                $this->promotion(type: 'percent', value: 20.0, promotableType: 'tire', promotableId: 99),
            ],
            productType: 'tire',
            productId: 42,
            brandId: 7,
            now: $this->now,
        );

        $this->assertNull($matched);
    }

    public function test_gift_promotion_keeps_price(): void
    {
        // Подарок цену не меняет, но остаётся выбранной акцией — фронту нужен признак
        $matched = PromotionMatcher::match(
            basePrice: 2000.0,
            promotions: [
                $this->promotion(type: 'gift', value: null, promotableType: 'tire', promotableId: 42),
            ],
            productType: 'tire',
            productId: 42,
            brandId: 7,
            now: $this->now,
        );

        $this->assertNotNull($matched);
        $this->assertSame('gift', $matched['type']);
    }

    public function test_catalog_promotion_applies_to_any_product(): void
    {
        $matched = PromotionMatcher::match(
            basePrice: 2000.0,
            promotions: [
                $this->promotion(type: 'percent', value: 15.0),
            ],
            productType: 'wheel',
            productId: 777,
            brandId: null,
            now: $this->now,
        );

        $this->assertSame(15.0, $matched['value']);
    }

    public function test_wheel_promotion_does_not_apply_to_tire(): void
    {
        $matched = PromotionMatcher::match(
            basePrice: 2000.0,
            promotions: [
                $this->promotion(type: 'percent', value: 15.0, promotableType: 'wheel', promotableId: 42),
            ],
            productType: 'tire',
            productId: 42,
            brandId: 7,
            now: $this->now,
        );

        $this->assertNull($matched);
    }

    /** @return array<string, mixed> */
    private function promotion(
        string $type,
        ?float $value,
        ?string $promotableType = null,
        ?int $promotableId = null,
        string $startsAt = '2026-09-01 00:00:00',
        string $endsAt = '2026-09-30 00:00:00',
    ): array {
        return [
            'type' => $type,
            'value' => $value,
            'promotable_type' => $promotableType,
            'promotable_id' => $promotableId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ];
    }
}
