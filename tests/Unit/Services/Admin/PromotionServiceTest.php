<?php

namespace Tests\Unit\Services\Admin;

use App\Services\Admin\PromotionService;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Разрешение типа привязки акции. */
class PromotionServiceTest extends TestCase
{
    private PromotionService $service;

    protected function setUp(): void
    {
        $this->service = new PromotionService;
    }

    /** @return array<string, array{string, string}> */
    public static function provideValidTypes(): array
    {
        return [
            'tire' => ['tire', 'tire_product'],
            'wheel' => ['wheel', 'wheel_product'],
            'brand' => ['brand', 'brand'],
        ];
    }

    #[DataProvider('provideValidTypes')]
    public function test_resolve_promotable_type(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->service->resolvePromotableType($input));
    }

    public function test_resolve_promotable_type_throws_on_unknown(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Некорректный тип привязки: unknown');

        $this->service->resolvePromotableType('unknown');
    }
}
