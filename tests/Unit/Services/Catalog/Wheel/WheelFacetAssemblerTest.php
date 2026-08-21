<?php

namespace Tests\Unit\Services\Catalog\Wheel;

use App\Services\Catalog\Wheel\WheelFacetAssembler;
use PHPUnit\Framework\TestCase;

/** Чистые функции ассемблера фасетов дисков — без БД. */
class WheelFacetAssemblerTest extends TestCase
{
    public function test_type_facet_uses_enum_labels(): void
    {
        $this->assertSame(
            [
                ['label' => 'Литые', 'value' => 'alloy'],
                ['label' => 'Стальные', 'value' => 'steel'],
            ],
            WheelFacetAssembler::type(['alloy', 'steel']),
        );
    }

    public function test_type_facet_excludes_absent_values(): void
    {
        $this->assertSame(
            [['label' => 'Кованые', 'value' => 'forged']],
            WheelFacetAssembler::type(['forged']),
        );
    }

    public function test_dimension_sorts_and_deduplicates(): void
    {
        $this->assertSame(
            [
                ['label' => '5.5', 'value' => '5.5'],
                ['label' => '6.5', 'value' => '6.5'],
            ],
            WheelFacetAssembler::dimension(['6.5', '5.5', '6.5']),
        );
    }

    public function test_named_sorts_by_name(): void
    {
        $this->assertSame(
            [
                ['label' => 'A', 'value' => 'a'],
                ['label' => 'B', 'value' => 'b'],
            ],
            WheelFacetAssembler::named([
                ['name' => 'B', 'slug' => 'b'],
                ['name' => 'A', 'slug' => 'a'],
            ]),
        );
    }

    public function test_delivery_buckets_min_days(): void
    {
        $this->assertSame(
            [
                ['label' => 'Сегодня', 'value' => 'today'],
                ['label' => 'После 5 дней', 'value' => 'after5days'],
            ],
            WheelFacetAssembler::delivery([0, 6]),
        );
    }
}
