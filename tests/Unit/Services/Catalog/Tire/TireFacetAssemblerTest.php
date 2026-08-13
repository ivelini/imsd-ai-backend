<?php

namespace Tests\Unit\Services\Catalog\Tire;

use App\Services\Catalog\Tire\TireFacetAssembler;
use PHPUnit\Framework\TestCase;

/** Сборка фасетов из сырых значений — чистые функции без БД. */
class TireFacetAssemblerTest extends TestCase
{
    public function test_dimension_sorts_numerically_and_deduplicates(): void
    {
        $this->assertSame([
            ['label' => 175, 'value' => 175],
            ['label' => 205, 'value' => 205],
            ['label' => 225, 'value' => 225],
        ], TireFacetAssembler::dimension([225, 175, 205, 175]));
    }

    public function test_dimension_sorts_diameter_strings_numerically(): void
    {
        $this->assertSame([
            ['label' => '15.5', 'value' => '15.5'],
            ['label' => '16', 'value' => '16'],
            ['label' => '17', 'value' => '17'],
        ], TireFacetAssembler::dimension(['17', '16', '15.5']));
    }

    public function test_season_keeps_enum_order_and_drops_absent_values(): void
    {
        $this->assertSame([
            ['label' => 'Зимняя', 'value' => 'winter'],
            ['label' => 'Летняя', 'value' => 'summer'],
        ], TireFacetAssembler::season(['summer', 'winter', 'all-season-extra']));
    }

    public function test_studded_maps_labels_with_studded_first(): void
    {
        $this->assertSame([
            ['label' => 'Шипованная', 'value' => true],
            ['label' => 'Не шипованная', 'value' => false],
        ], TireFacetAssembler::studded([false, true, true]));
    }

    public function test_named_sorts_by_name_and_maps_slug(): void
    {
        $this->assertSame([
            ['label' => 'Kama', 'value' => 'kama'],
            ['label' => 'Nokian', 'value' => 'nokian'],
        ], TireFacetAssembler::named([
            ['slug' => 'nokian', 'name' => 'Nokian'],
            ['slug' => 'kama', 'name' => 'Kama'],
        ]));
    }

    public function test_delivery_keeps_fixed_bucket_order_and_skips_empty(): void
    {
        $this->assertSame([
            ['label' => 'Сегодня', 'value' => 'today'],
            ['label' => 'От 1 до 3 дней', 'value' => 'between1and3days'],
        ], TireFacetAssembler::delivery([3, 0, 2]));
    }

    public function test_delivery_maps_after_five_bucket(): void
    {
        $this->assertSame([
            ['label' => 'После 5 дней', 'value' => 'after5days'],
        ], TireFacetAssembler::delivery([7]));
    }

    public function test_price_range_null_becomes_zero(): void
    {
        $this->assertSame(['min' => 0.0, 'max' => 0.0], TireFacetAssembler::priceRange(null, null));
        $this->assertSame(['min' => 100.5, 'max' => 500.0], TireFacetAssembler::priceRange(100.5, 500.0));
    }
}
