<?php

namespace Tests\Unit\Services\Catalog\Tire;

use App\Enums\Catalog\Season;
use App\Services\Catalog\Tire\TireNameBuilder;
use PHPUnit\Framework\TestCase;

/** Формула отображаемого имени шины — чистая функция без БД. */
class TireNameBuilderTest extends TestCase
{
    public function test_build_full_name(): void
    {
        $this->assertSame(
            'Шина зимняя Gislaved Soft Frost 200 195/55 R16 91T',
            TireNameBuilder::build(
                season: Season::Winter,
                brandName: 'Gislaved',
                modelName: 'Soft Frost 200',
                width: 195,
                profile: 55,
                diameter: '16',
                loadIndex: '91',
                speedIndex: 'T',
            ),
        );
    }

    public function test_build_omits_missing_parts(): void
    {
        $this->assertSame(
            'Шина летняя Gislaved Soft Frost 200 195/55',
            TireNameBuilder::build(
                season: Season::Summer,
                brandName: 'Gislaved',
                modelName: 'Soft Frost 200',
                width: 195,
                profile: 55,
                diameter: null,
                loadIndex: null,
                speedIndex: null,
            ),
        );
    }

    public function test_build_all_season_lowercase(): void
    {
        $this->assertSame(
            'Шина всесезон Gislaved Soft Frost 200 195/55 R16',
            TireNameBuilder::build(
                season: Season::AllSeason,
                brandName: 'Gislaved',
                modelName: 'Soft Frost 200',
                width: 195,
                profile: 55,
                diameter: '16',
                loadIndex: null,
                speedIndex: null,
            ),
        );
    }
}
