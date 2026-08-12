<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Import;

use App\Services\Import\ColumnDetector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ColumnDetectorTest extends TestCase
{
    #[Test]
    public function test_detect_price_range_columns(): void
    {
        $headers = ['code', '0-5000', '5001-8500', 'name'];

        $result = ColumnDetector::detectPriceColumns($headers);

        $this->assertSame(['0-5000', '5001-8500'], $result);
    }

    #[Test]
    public function test_detect_delivery_coeff_columns(): void
    {
        $headers = ['code', '31-60 кг', '61-100кг', 'name'];

        $result = ColumnDetector::detectDeliveryColumns($headers);

        $this->assertSame(['31-60 кг', '61-100кг'], $result);
    }

    #[Test]
    public function test_detect_delivery_coeff_columns_without_space(): void
    {
        $headers = ['61-100кг', 'name'];

        $result = ColumnDetector::detectDeliveryColumns($headers);

        $this->assertSame(['61-100кг'], $result);
    }

    #[Test]
    public function test_detect_none_returns_empty(): void
    {
        $headers = ['code', 'name', 'city'];

        $price = ColumnDetector::detectPriceColumns($headers);
        $delivery = ColumnDetector::detectDeliveryColumns($headers);

        $this->assertSame([], $price);
        $this->assertSame([], $delivery);
    }
}
