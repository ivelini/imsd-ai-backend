<?php

namespace Tests\Unit\Services\TireImport;

use App\Services\TireImport\DescriptionBuilder;
use PHPUnit\Framework\TestCase;

/** Сборка JSONB-описания — чистая функция. */
class DescriptionBuilderTest extends TestCase
{
    private DescriptionBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new DescriptionBuilder;
    }

    public function test_build_filters_empty_values(): void
    {
        $result = $this->builder->build([
            'vendor' => 'Vendor desc',
            'default' => '',
            'manufacture_country' => null,
        ]);

        $this->assertArrayHasKey('vendor', $result);
        $this->assertArrayNotHasKey('default', $result);
        $this->assertArrayNotHasKey('manufacture_country', $result);
    }

    public function test_build_keeps_all_filled(): void
    {
        $result = $this->builder->build([
            'vendor' => 'A',
            'default' => 'B',
            'manufacture_country' => 'C',
        ]);

        $this->assertCount(3, $result);
    }
}
