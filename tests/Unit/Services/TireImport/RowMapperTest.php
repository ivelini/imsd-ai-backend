<?php

namespace Tests\Unit\Services\TireImport;

use App\DTOs\Catalog\Tire\EuroLabel;
use App\Services\TireImport\RowMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Чистые функции маппинга строк XLSX — без БД и HTTP. */
class RowMapperTest extends TestCase
{
    private RowMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new RowMapper;
    }

    public function test_map_creates_row_with_ean(): void
    {
        $row = $this->mapper->map([
            'product_article' => 'AA-001',
            'vendor' => 'TestBrand',
            'name' => 'Test Model',
            'season' => 'зимняя',
            'width' => '205',
            'height' => '55',
            'diameter' => 'R16',
            'load_speed_index' => '91V',
        ]);

        $this->assertSame('AA-001', $row->ean);
        $this->assertSame('TestBrand', $row->brand_name);
        $this->assertSame('Test Model', $row->name);
    }

    public function test_map_supports_mapped_keys(): void
    {
        $row = $this->mapper->map([
            'ean' => 'AA-002',
            'brand_name' => 'Brand',
            'name' => 'Model 2',
            'season_raw' => 'лето',
            'width' => '195',
            'profile' => '65',
            'diameter' => '15',
        ]);

        $this->assertSame('AA-002', $row->ean);
    }

    public function test_parse_euro_label_parses_valid_format(): void
    {
        $label = $this->mapper->parseEuroLabel('D/C/71');

        $this->assertInstanceOf(EuroLabel::class, $label);
        $this->assertSame('D', $label->rollingResistance);
        $this->assertSame('C', $label->wetGrip);
        $this->assertSame('71', $label->noiseEmission);
    }

    public function test_parse_euro_label_uppercases_letters(): void
    {
        $label = $this->mapper->parseEuroLabel('d/c/71');

        $this->assertSame('D', $label?->rollingResistance);
        $this->assertSame('C', $label?->wetGrip);
    }

    #[DataProvider('provideInvalidEuroLabels')]
    public function test_parse_euro_label_returns_null_for_invalid(?string $input): void
    {
        $this->assertNull($this->mapper->parseEuroLabel($input));
    }

    /** @return array<string, array{string|null}> */
    public static function provideInvalidEuroLabels(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'two_segments' => ['D/C'],
            'four_segments' => ['D/C/71/1'],
            'rolling_not_letter' => ['1/D/71'],
            'wet_out_of_range' => ['D/1/71'],
            'noise_not_number' => ['D/C/7X'],
        ];
    }

    public function test_map_extracts_euro_label(): void
    {
        $withLabel = $this->mapper->map(['description_euro_label' => 'D/C/71']);

        $this->assertSame('D', $withLabel->euroLabel?->rollingResistance);

        $withoutLabel = $this->mapper->map([]);

        $this->assertNull($withoutLabel->euroLabel);
    }

    public function test_map_extracts_description_and_presence_flags(): void
    {
        $row = $this->mapper->map(['description' => 'Описание', 'origin_vendor' => '##Badge##']);

        $this->assertSame('Описание', $row->description);
        $this->assertTrue($row->description_present);
        $this->assertSame('Badge', $row->origin_vendor?->badge);
        $this->assertTrue($row->origin_present);

        $empty = $this->mapper->map([]);

        $this->assertNull($empty->description);
        $this->assertFalse($empty->description_present);
        $this->assertFalse($empty->origin_present);
    }

    public function test_nullable_int_returns_null_for_empty(): void
    {
        $this->assertNull($this->mapper->nullableInt(''));
        $this->assertNull($this->mapper->nullableInt(null));
        $this->assertNull($this->mapper->nullableInt(false));
        $this->assertSame(5, $this->mapper->nullableInt('5'));
    }

    public function test_nullable_float_handles_russian_format(): void
    {
        $this->assertNull($this->mapper->nullableFloat(''));
        $this->assertSame(3395.0, $this->mapper->nullableFloat('3395'));
        $this->assertSame(1234.56, $this->mapper->nullableFloat('1 234,56'));
    }

    #[DataProvider('provideBooleanValues')]
    public function test_to_bool(string $input, bool $expected): void
    {
        $this->assertSame($expected, $this->mapper->toBool($input));
    }

    /** @return array<string, array{string, bool}> */
    public static function provideBooleanValues(): array
    {
        return [
            'да' => ['да', true],
            'true' => ['true', true],
            'нет' => ['нет', false],
            'empty' => ['', false],
        ];
    }

    #[DataProvider('provideSeasonValues')]
    public function test_to_season(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->mapper->toSeason($input));
    }

    /** @return array<string, array{string, string}> */
    public static function provideSeasonValues(): array
    {
        return [
            'зимняя' => ['зимняя', 'winter'],
            'летняя' => ['летняя', 'summer'],
            'всесезон' => ['всесезон', 'all-season'],
            'fallback' => ['неизвестно', 'summer'],
            'null' => ['', 'summer'],
        ];
    }

    #[DataProvider('provideLoadSpeedIndex')]
    public function test_parse_load_speed_index(string $input, ?string $expectedLoad, ?string $expectedSpeed): void
    {
        $result = $this->mapper->parseLoadSpeedIndex($input);

        $this->assertSame($expectedLoad, $result['load']);
        $this->assertSame($expectedSpeed, $result['speed']);
    }

    /** @return array<string, array{string, string|null, string|null}> */
    public static function provideLoadSpeedIndex(): array
    {
        return [
            '86T' => ['86T', '86', 'T'],
            '91V' => ['91V', '91', 'V'],
            'only_load' => ['100', '100', null],
            'empty' => ['', null, null],
        ];
    }
}
