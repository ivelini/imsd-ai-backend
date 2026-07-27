<?php

namespace Tests\Unit\Services\TireImport;

use App\Services\TireImport\SlugGenerator;
use PHPUnit\Framework\TestCase;

/** Генерация slug из названия — чистая функция. */
class SlugGeneratorTest extends TestCase
{
    public function test_generates_slug(): void
    {
        $this->assertSame('test-brand', SlugGenerator::fromName('Test Brand'));
    }

    public function test_handles_cyrillic(): void
    {
        $this->assertSame('шины-бренд', SlugGenerator::fromName('Шины Бренд'));
    }

    public function test_trims_to_max_length(): void
    {
        $long = str_repeat('a', 100);
        $slug = SlugGenerator::fromName($long, 10);

        $this->assertLessThanOrEqual(10, strlen($slug));
    }
}
