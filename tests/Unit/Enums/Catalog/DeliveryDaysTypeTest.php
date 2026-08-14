<?php

namespace Tests\Unit\Enums\Catalog;

use App\Enums\Catalog\DeliveryDaysType;
use PHPUnit\Framework\TestCase;

/** Бакеты срока доставки: границы fromDays и лейблы. */
class DeliveryDaysTypeTest extends TestCase
{
    public function test_from_days_maps_boundaries(): void
    {
        $this->assertSame(DeliveryDaysType::ToDay, DeliveryDaysType::fromDays(0));
        $this->assertSame(DeliveryDaysType::Between1and3days, DeliveryDaysType::fromDays(1));
        $this->assertSame(DeliveryDaysType::Between1and3days, DeliveryDaysType::fromDays(3));
        $this->assertSame(DeliveryDaysType::Between3and5days, DeliveryDaysType::fromDays(4));
        $this->assertSame(DeliveryDaysType::Between3and5days, DeliveryDaysType::fromDays(5));
    }

    public function test_from_days_maps_after_five(): void
    {
        $this->assertSame(DeliveryDaysType::After5days, DeliveryDaysType::fromDays(6));
        $this->assertSame(DeliveryDaysType::After5days, DeliveryDaysType::fromDays(100));
    }

    public function test_min_days_maps_bucket_boundaries(): void
    {
        $this->assertSame(0, DeliveryDaysType::ToDay->minDays());
        $this->assertSame(1, DeliveryDaysType::Between1and3days->minDays());
        $this->assertSame(4, DeliveryDaysType::Between3and5days->minDays());
        $this->assertSame(6, DeliveryDaysType::After5days->minDays());
    }

    public function test_max_days_maps_bucket_boundaries(): void
    {
        $this->assertSame(0, DeliveryDaysType::ToDay->maxDays());
        $this->assertSame(3, DeliveryDaysType::Between1and3days->maxDays());
        $this->assertSame(5, DeliveryDaysType::Between3and5days->maxDays());
        $this->assertNull(DeliveryDaysType::After5days->maxDays());
    }
}
