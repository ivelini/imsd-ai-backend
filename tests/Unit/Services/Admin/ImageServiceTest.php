<?php

namespace Tests\Unit\Services\Admin;

use App\Services\Admin\ImageService;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Бизнес-логика изображений: лимит, morph type, наследование главного. */
class ImageServiceTest extends TestCase
{
    private ImageService $service;

    protected function setUp(): void
    {
        $this->service = new ImageService;
    }

    /** @return array<string, array{string, string}> */
    public static function provideValidTypes(): array
    {
        return [
            'tire' => ['tire', 'tire_product'],
            'wheel' => ['wheel', 'wheel_product'],
        ];
    }

    #[DataProvider('provideValidTypes')]
    public function test_resolve_morph_type(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->service->resolveMorphType($input));
    }

    public function test_resolve_morph_type_throws_on_unknown(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Некорректный тип товара: unknown');

        $this->service->resolveMorphType('unknown');
    }

    public function test_ensure_image_limit_passes_below_max(): void
    {
        $this->service->ensureImageLimit(5);
        $this->expectNotToPerformAssertions();
    }

    public function test_ensure_image_limit_throws_at_max(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Не более 10 изображений на товар.');

        $this->service->ensureImageLimit(10);
    }

    public function test_get_next_main_image_returns_null_when_not_main(): void
    {
        $this->assertNull($this->service->getNextMainImageId(false, [1, 2, 3]));
    }

    public function test_get_next_main_image_returns_null_when_no_siblings(): void
    {
        $this->assertNull($this->service->getNextMainImageId(true, []));
    }

    public function test_get_next_main_image_returns_first_sibling(): void
    {
        $this->assertSame(5, $this->service->getNextMainImageId(true, [5, 6, 7]));
    }
}
